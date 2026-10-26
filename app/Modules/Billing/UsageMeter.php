<?php

namespace App\Modules\Billing;

use App\Shared\Tenancy\TenantContext;
use App\Shared\Tenancy\WorkspaceLocale;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Atomic daily counters per tenant and metric (US-E13-01). Days and months
 * are the workspace's local calendar (ADR-012).
 */
class UsageMeter
{
    public const CARTS_ABANDONED = 'carts_abandoned';

    public const ELIGIBLE_CARTS = 'eligible_carts';

    public const MESSAGES_DELIVERED = 'messages_delivered';

    public const AI_CALLS = 'ai_calls';

    /** Micro-dollars, so the founder sees AI cost per tenant per day (US-E10-02). */
    public const AI_COST_USD_MICROS = 'ai_cost_usd_micros';

    public const AI_DRAFTS = 'ai_drafts';

    public function __construct(
        private readonly TenantContext $tenants,
        private readonly WorkspaceLocale $locale,
    ) {}

    public function increment(string $metric, int $by = 1, ?CarbonImmutable $at = null): void
    {
        $day = ($at ?? CarbonImmutable::now())->setTimezone($this->locale->timezone())->toDateString();

        DB::statement(
            'INSERT INTO usage_daily (tenant_id, metric, day, count) VALUES (?, ?, ?, ?)
             ON CONFLICT (tenant_id, metric, day) DO UPDATE SET count = usage_daily.count + EXCLUDED.count',
            [$this->tenants->require(), $metric, $day, $by],
        );
    }

    /**
     * @return array<string, int> metric => total for the month (local calendar)
     */
    public function month(string $yearMonth): array
    {
        $start = CarbonImmutable::parse($yearMonth.'-01', $this->locale->timezone());

        /** @var array<string, int> */
        return DB::table('usage_daily')
            ->where('tenant_id', $this->tenants->require())
            ->whereBetween('day', [$start->toDateString(), $start->endOfMonth()->toDateString()])
            ->groupBy('metric')
            ->selectRaw('metric, sum(count)::bigint as total')
            ->pluck('total', 'metric')
            ->map(fn ($total) => (int) $total)
            ->all();
    }
}
