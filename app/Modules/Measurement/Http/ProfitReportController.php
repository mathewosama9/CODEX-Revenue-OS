<?php

namespace App\Modules\Measurement\Http;

use App\Modules\Measurement\Models\ProfitReportSnapshot;
use App\Modules\Measurement\ProfitReport;
use App\Modules\Measurement\ReportPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfitReportController
{
    /** Live report for any period of whole Riyadh days (default: the last 28 days, today included). */
    public function show(Request $request, ProfitReport $report): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $period = isset($validated['from'], $validated['to'])
            ? ReportPeriod::days($validated['from'], $validated['to'])
            : ReportPeriod::lastDays(28);

        return response()->json(['data' => $report->compute($period->start, $period->end)]);
    }

    /** Weekly snapshots the merchant already received; never recomputed. */
    public function snapshots(): JsonResponse
    {
        return response()->json(['data' => ProfitReportSnapshot::query()
            ->orderByDesc('period_end')
            ->limit(26)
            ->get()
            ->map(fn (ProfitReportSnapshot $snapshot) => [
                'period_start' => $snapshot->period_start->toDateString(),
                'period_end' => $snapshot->period_end->toDateString(),
                'method_version' => $snapshot->method_version,
                'report' => $snapshot->payload,
            ])->values()]);
    }
}
