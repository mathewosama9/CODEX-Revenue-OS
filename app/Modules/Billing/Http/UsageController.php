<?php

namespace App\Modules\Billing\Http;

use App\Modules\Billing\UsageMeter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Monthly usage for the manual invoice (US-E13-02): JSON, or CSV to attach.
 */
class UsageController
{
    public function __invoke(Request $request, UsageMeter $meter): JsonResponse|StreamedResponse
    {
        $validated = $request->validate([
            'month' => ['required', 'date_format:Y-m'],
            'format' => ['nullable', 'in:json,csv'],
        ]);

        $usage = $meter->month($validated['month']);

        if (($validated['format'] ?? 'json') === 'csv') {
            return response()->streamDownload(function () use ($usage, $validated) {
                $out = fopen('php://output', 'w');
                fputcsv($out, ['month', 'metric', 'count']);
                foreach ($usage as $metric => $count) {
                    fputcsv($out, [$validated['month'], $metric, $count]);
                }
                fclose($out);
            }, "usage-{$validated['month']}.csv", ['Content-Type' => 'text/csv']);
        }

        return response()->json(['data' => ['month' => $validated['month'], 'usage' => $usage]]);
    }
}
