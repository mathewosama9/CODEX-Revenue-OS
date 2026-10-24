<?php

namespace App\Modules\Measurement\Http;

use App\Modules\Commerce\Commerce;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Measurement\MarginLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * E08: the merchant's approximate margin per category (5–10 numbers at onboarding).
 */
class MarginsController
{
    public function __construct(
        private readonly MarginLookup $margins,
        private readonly Commerce $commerce,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): JsonResponse
    {
        $current = $this->margins->currentByCategory();
        $default = $this->margins->resolve(null);

        return response()->json(['data' => [
            'default' => ['margin_bps' => $default['bps'], 'source' => $default['source']],
            'categories' => array_map(fn (array $category) => $category + [
                'margin_bps' => $current[$category['external_id']] ?? null,
            ], $this->commerce->categories()),
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_margin_bps' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'categories' => ['array', 'max:200'],
            'categories.*.external_id' => ['required', 'string', 'max:64'],
            'categories.*.margin_bps' => ['required', 'integer', 'min:0', 'max:10000'],
        ]);

        $byCategory = [];

        foreach ($validated['categories'] ?? [] as $row) {
            $byCategory[(string) $row['external_id']] = (int) $row['margin_bps'];
        }

        $this->margins->update($validated['default_margin_bps'] ?? null, $byCategory, $request->user()?->id);

        // Margins drive discount floors, so every change is audited (E08).
        $this->audit->record('margins.updated', ActorType::User, $request->user()?->id, context: [
            'default_changed' => isset($validated['default_margin_bps']),
            'categories_changed' => count($byCategory),
        ]);

        return $this->index();
    }
}
