<?php

namespace App\Modules\SalesAgent\Http;

use App\Modules\AiGateway\AiGateway;
use App\Modules\SalesAgent\AgentControls;
use App\Modules\SalesAgent\AgentMode;
use App\Modules\SalesAgent\BlockRateMonitor;
use App\Modules\SalesAgent\Models\AgentSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * AI settings screen: the mode (autonomy stays locked until its conditions
 * are met), the daily cap, and the numbers behind them.
 */
class AgentController
{
    public function __construct(
        private readonly AgentControls $controls,
        private readonly AiGateway $gateway,
        private readonly BlockRateMonitor $monitor,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json(['data' => $this->payload($this->controls->settings())]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['sometimes', Rule::enum(AgentMode::class)],
            'confirmation' => ['nullable', 'string', 'max:100'],
            'daily_cap_usd' => ['sometimes', 'numeric', 'min:0.1', 'max:1000'],
            'handoff_cart_sar' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
        ]);
        $userId = (int) $request->user()?->id;

        if (isset($validated['daily_cap_usd']) || isset($validated['handoff_cart_sar'])) {
            $this->controls->updateLimits(
                $userId,
                isset($validated['daily_cap_usd']) ? (int) round((float) $validated['daily_cap_usd'] * 1_000_000) : null,
                isset($validated['handoff_cart_sar']) ? (int) $validated['handoff_cart_sar'] * 100 : null,
            );
        }

        if (isset($validated['mode'])) {
            $this->controls->setMode($userId, AgentMode::from($validated['mode']), $validated['confirmation'] ?? null);
        }

        return $this->show();
    }

    /** The AI kill switch: anyone allowed to stop the AI, agents included (US-E14-04). */
    public function stop(Request $request): JsonResponse
    {
        $this->controls->setMode((int) $request->user()?->id, AgentMode::Off);

        return $this->show();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(AgentSettings $settings): array
    {
        return [
            'mode' => $settings->mode->value,
            'daily_cap_usd' => $settings->daily_cap_usd_micros / 1_000_000,
            'max_daily_cap_usd' => $this->controls->defaultCapUsdMicros() / 1_000_000,
            'spent_today_usd' => round($this->gateway->spentTodayUsdMicros() / 1_000_000, 4),
            'handoff_cart_sar' => $settings->handoff_cart_halalas === null ? null : intdiv($settings->handoff_cart_halalas, 100),
            'assisted_days' => $this->controls->assistedDays($settings),
            'assisted_days_required' => AgentControls::ASSISTED_DAYS_BEFORE_AUTONOMY,
            'autonomy_blockers' => $this->controls->autonomyBlockers($settings),
            'autonomy_confirmation' => AgentControls::AUTONOMY_CONFIRMATION,
            'auto_downgraded_at' => $settings->auto_downgraded_at?->toIso8601String(),
            'draft_quality' => $this->controls->draftQuality(),
            'validator' => $this->monitor->rate(),
            'model' => $this->gateway->model(),
        ];
    }
}
