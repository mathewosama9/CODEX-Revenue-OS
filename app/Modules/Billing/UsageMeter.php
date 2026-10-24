<?php

namespace App\Modules\Billing;

use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Atomic daily counters per tenant and metric (US-E13-01).
 */
class UsageMeter
{
    public const CARTS_ABANDONED = 'carts_abandoned';

    public const ELIGIBLE_CARTS = 'eligible_carts';

    public const MESSAGES_DELIVERED = 'messages_delivered';

    public function __construct(private readonly TenantContext $tenants) {}

    public function increment(string $metric, int $by = 1, ?CarbonImmutable $at = null): void
    {
        $day = ($at ?? CarbonImmutable::now())->setTimezone('Asia/Riyadh')->toDateString();

        DB::statement(
            'INSERT INTO usage_daily (tenant_id, metric, day, count) VALUES (?, ?, ?, ?)
             ON CONFLICT (tenant_id, metric, day) DO UPDATE SET count = usage_daily.count + EXCLUDED.count',
            [$this->tenants->require(), $metric, $day, $by],
        );
    }

    /**
     * @return array<string, int> metric => total for the month (Riyadh calendar)
     */
    public function month(string $yearMonth): array
    {
        $start = CarbonImmutable::parse($yearMonth.'-01', 'Asia/Riyadh');

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
