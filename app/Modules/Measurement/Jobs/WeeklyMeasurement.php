<?php

namespace App\Modules\Measurement\Jobs;

use App\Modules\Measurement\Models\ProfitReportSnapshot;
use App\Modules\Measurement\ProfitReport;
use App\Modules\Measurement\SampleRatioCheck;
use App\Shared\Tenancy\TenantAware;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * SRM check, then a frozen weekly snapshot of the trailing 28 days.
 */
class WeeklyMeasurement implements ShouldQueue
{
    use Dispatchable, Queueable, TenantAware;

    public function __construct(public int $tenantId) {}

    public function handle(SampleRatioCheck $srm, ProfitReport $report): void
    {
        $srm->run();

        $to = CarbonImmutable::now()->startOfDay();
        $from = $to->subDays(28);

        ProfitReportSnapshot::create([
            'period_start' => $from,
            'period_end' => $to,
            'method_version' => (string) config('codex.measurement.method_version'),
            'payload' => $report->compute($from, $to),
        ]);
    }
}
