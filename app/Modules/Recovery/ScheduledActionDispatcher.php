<?php

namespace App\Modules\Recovery;

use App\Modules\Recovery\Jobs\ExecuteScheduledAction;
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

    public function dispatchDue(): int
    {
        $now = CarbonImmutable::now();

        /** @var list<object{id: int, tenant_id: int}> $claimed */
        $claimed = DB::transaction(function () use ($now) {
            $rows = DB::table('scheduled_actions')
                ->select(['id', 'tenant_id'])
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
                ->get()
                ->all();

            if ($rows !== []) {
                DB::table('scheduled_actions')
                    ->whereIn('id', array_map(fn ($row) => $row->id, $rows))
                    ->update(['status' => ActionStatus::Claimed->value, 'claimed_at' => $now]);
            }

            return $rows;
        });

        // A re-dispatched stale claim is safe: the sender is idempotent per step.
        foreach ($claimed as $row) {
            ExecuteScheduledAction::dispatch((int) $row->tenant_id, (int) $row->id);
        }

        return count($claimed);
    }
}
