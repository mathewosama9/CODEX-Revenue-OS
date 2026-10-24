<?php

namespace App\Modules\Measurement\Http;

use App\Modules\Measurement\Models\ProfitReportSnapshot;
use App\Modules\Measurement\ProfitReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfitReportController
{
    /** Live report for any period (default: the last 28 days). */
    public function show(Request $request, ProfitReport $report): JsonResponse
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after:from'],
        ]);

        $to = isset($validated['to']) ? CarbonImmutable::parse($validated['to']) : CarbonImmutable::now()->startOfDay();
        $from = isset($validated['from']) ? CarbonImmutable::parse($validated['from']) : $to->subDays(28);

        return response()->json(['data' => $report->compute($from, $to)]);
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
