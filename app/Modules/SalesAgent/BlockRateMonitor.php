<?php

namespace App\Modules\SalesAgent;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\SalesAgent\Models\AgentRun;
use App\Modules\SalesAgent\Models\AgentSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * US-E10-07: if the validator blocks more than 2% of a tenant's model runs
 * over a rolling week, an autonomous tenant goes back to Assisted until a
 * person reviews it.
 */
class BlockRateMonitor
{
    public const MAX_BLOCK_RATE = 0.02;

    public const WINDOW_DAYS = 7;

    /** Below this, one block is noise, not a trend. [اقتراح] */
    public const MIN_RUNS = 50;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @return array{runs: int, blocked: int, rate: float|null}
     */
    public function rate(): array
    {
        $row = AgentRun::query()
            ->where('created_at', '>=', CarbonImmutable::now()->subDays(self::WINDOW_DAYS))
            ->where('attempts', '>', 0)
            ->selectRaw('count(*) as runs, count(*) filter (where validator_blocks > 0) as blocked')
            ->first();

        $runs = (int) ($row->runs ?? 0);
        $blocked = (int) ($row->blocked ?? 0);

        return ['runs' => $runs, 'blocked' => $blocked, 'rate' => $runs === 0 ? null : round($blocked / $runs, 4)];
    }

    public function check(AgentSettings $settings): void
    {
        if ($settings->mode !== AgentMode::ScopedAutonomous) {
            return;
        }

        $rate = $this->rate();

        if ($rate['runs'] < self::MIN_RUNS || $rate['rate'] <= self::MAX_BLOCK_RATE) {
            return;
        }

        $settings->update(['mode' => AgentMode::Assisted, 'auto_downgraded_at' => CarbonImmutable::now()]);
        $this->audit->record('ai.auto_downgraded', ActorType::System, subject: $settings, context: $rate);
        Log::warning('AI agent switched back to Assisted: validator block rate over 2%.', ['tenant_id' => $settings->tenant_id] + $rate);
    }
}
