<?php

namespace App\Modules\Measurement\Jobs;

use App\Modules\Measurement\Models\ProfitReportSnapshot;
use App\Modules\Measurement\ProfitReport;
use App\Modules\Measurement\ReportPeriod;
use App\Modules\Measurement\SampleRatioCheck;
use App\Shared\Tenancy\TenantAware;
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

        // The 28 whole local days before today (review H6, ADR-012).
        $period = ReportPeriod::daysBeforeToday(28);

        ProfitReportSnapshot::create([
            'period_start' => ReportPeriod::firstDay($period->start),
            'period_end' => ReportPeriod::lastDay($period->end),
            'method_version' => (string) config('codex.measurement.method_version'),
            'payload' => $report->compute($period->start, $period->end),
        ]);
    }
}
