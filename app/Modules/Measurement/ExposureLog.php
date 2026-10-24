<?php

namespace App\Modules\Measurement;

use App\Modules\Billing\UsageMeter;
use App\Modules\Measurement\Models\Exposure;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * The denominator of the experiment: every cart that passed the same
 * eligibility checks, in either arm, at the moment of decision.
 */
class ExposureLog
{
    public function __construct(
        private readonly ExperimentAssigner $assigner,
        private readonly UsageMeter $usage,
    ) {}

    public function record(int $cartId, string $subjectHash, Arm $arm, int $cartTotalHalalas, CarbonImmutable $eligibleAt): void
    {
        try {
            DB::transaction(fn () => Exposure::create([
                'experiment_id' => $this->assigner->experiment()->id,
                'subject_hash' => $subjectHash,
                'arm' => $arm->value,
                'cart_id' => $cartId,
                'cart_total_halalas' => $cartTotalHalalas,
                'eligible_at' => $eligibleAt,
            ]));

            $this->usage->increment(UsageMeter::ELIGIBLE_CARTS, at: $eligibleAt);
        } catch (UniqueConstraintViolationException) {
            // Same cart re-evaluated: the first decision stands (intent-to-treat).
        }
    }
}
