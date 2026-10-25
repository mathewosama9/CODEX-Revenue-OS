<?php

namespace App\Modules\Recovery;

use App\Modules\Recovery\Jobs\ExecuteScheduledAction;
use App\Modules\Recovery\Models\ScheduledAction;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Runs every minute (ADR-010). Claims due rows across all tenants with
 * FOR UPDATE SKIP LOCKED, so two schedulers never take the same row, then
 * hands each one to a tenant-scoped job. This is the one cross-tenant read in
 * Recovery; it touches ids only and never business data.
 */
class ScheduledActionDispatcher
{
    private const BATCH = 200;

    /** A claimed row older than this is assumed lost by a crashed worker. */
    private const CLAIM_LEASE_MINUTES = 10;

    /**
     * After this many lost runs the action is a poison pill (it crashes its
     * worker every time): fail it so it shows in health instead of re-running
     * every 10 minutes forever (review H1).
     */
    private const MAX_RECLAIMS = 3;

    public function __construct(
        private readonly TenantContext $tenants,
        private readonly RecoveryLifecycle $lifecycle,
    ) {}

    public function dispatchDue(): int
    {
        $now = CarbonImmutable::now();

        /** @var array{0: list<object{id: int, tenant_id: int}>, 1: list<object{id: int, tenant_id: int}>} $result */
        $result = DB::transaction(function () use ($now) {
            $rows = DB::table('scheduled_actions')
                ->select(['id', 'tenant_id', 'status', 'reclaims'])
                ->where(function ($query) use ($now) {
                    $query->whereIn('status', [ActionStatus::Pending->value, ActionStatus::AwaitingApproval->value])
                        ->where('run_at', '<=', $now);
                })
                ->orWhere(function ($query) use ($now) {
                    $query->where('status', ActionStatus::Claimed->value)
                        ->where('claimed_at', '<', $now->subMinutes(self::CLAIM_LEASE_MINUTES));
                })
                ->orderBy('run_at')
                ->limit(self::BATCH)
                ->lock('for update skip locked')
                ->get();

            $stale = $rows->filter(fn ($row) => $row->status === ActionStatus::Claimed->value);
            $poisoned = $stale->filter(fn ($row) => $row->reclaims + 1 >= self::MAX_RECLAIMS);
            $claimed = $rows->reject(fn ($row) => $poisoned->contains('id', $row->id));

            if ($claimed->isNotEmpty()) {
                DB::table('scheduled_actions')
                    ->whereIn('id', $claimed->pluck('id'))
                    ->update(['status' => ActionStatus::Claimed->value, 'claimed_at' => $now]);
            }

            if ($stale->isNotEmpty()) {
                DB::table('scheduled_actions')->whereIn('id', $stale->pluck('id'))->increment('reclaims');
            }

            return [$claimed->values()->all(), $poisoned->values()->all()];
        });

        [$claimed, $poisoned] = $result;

        foreach ($poisoned as $row) {
            $this->tenants->run((int) $row->tenant_id, function () use ($row) {
                $action = ScheduledAction::query()->find((int) $row->id);

                if ($action !== null) {
                    $this->lifecycle->failAction($action, 'stuck_after_reclaims');
                }
            });
        }

        // A re-dispatched stale claim is safe: the sender is idempotent per step.
        foreach ($claimed as $row) {
            ExecuteScheduledAction::dispatch((int) $row->tenant_id, (int) $row->id);
        }

        return count($claimed);
    }
}
