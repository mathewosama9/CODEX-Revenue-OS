<?php

namespace App\Modules\Measurement;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Measurement\Models\ExperimentAssignment;
use App\Shared\Stats\Normal;
use Carbon\CarbonImmutable;

/**
 * Daily sample-ratio check (US-E06-04): if the observed split is too far from
 * the configured holdout (p < 0.001), the assignment is broken and the report
 * is frozen until someone looks.
 */
class SampleRatioCheck
{
    private const MIN_SAMPLE = 200;

    private const ALPHA = 0.001;

    public function __construct(
        private readonly ExperimentAssigner $assigner,
        private readonly AuditLogger $audit,
    ) {}

    public function run(): ?float
    {
        $experiment = $this->assigner->experiment();

        $counts = ExperimentAssignment::query()
            ->where('experiment_id', $experiment->id)
            ->selectRaw('arm, count(*) as n')
            ->groupBy('arm')
            ->pluck('n', 'arm');

        $control = (int) ($counts['control'] ?? 0);
        $total = $control + (int) ($counts['treatment'] ?? 0);

        if ($total < self::MIN_SAMPLE || $experiment->holdout_bps <= 0 || $experiment->holdout_bps >= 10_000) {
            return null;
        }

        $expectedControl = $total * $experiment->holdout_bps / 10_000;
        $expectedTreatment = $total - $expectedControl;
        $treatment = $total - $control;

        $chi = ($control - $expectedControl) ** 2 / $expectedControl + ($treatment - $expectedTreatment) ** 2 / $expectedTreatment;
        $p = Normal::chiSquare1P($chi);

        if ($p < self::ALPHA && $experiment->srm_flagged_at === null) {
            $experiment->update(['srm_flagged_at' => CarbonImmutable::now()]);
            $this->audit->record('experiment.srm_flagged', ActorType::System, subject: $experiment, context: [
                'control' => $control,
                'treatment' => $treatment,
                'p_value' => $p,
            ]);
        }

        return $p;
    }
}
