<?php

namespace App\Modules\Measurement;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Measurement\Models\Experiment;
use App\Modules\Measurement\Models\ExperimentAssignment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Customer-level holdout with a stable hash (US-E06-01): the same customer is
 * always in the same arm, across carts and over time. The assignment is
 * stored for audit, but the hash alone decides it.
 */
class ExperimentAssigner
{
    public const CART_RECOVERY = 'cart_recovery';

    public function __construct(
        private readonly int $defaultHoldoutBps,
        private readonly AuditLogger $audit,
    ) {}

    public function experiment(string $key = self::CART_RECOVERY): Experiment
    {
        return Experiment::query()->firstOrCreate(['key' => $key], [
            'holdout_bps' => $this->defaultHoldoutBps,
            'salt' => Str::random(32),
            'holdout_locked' => true,
        ]);
    }

    public function arm(string $subjectHash, string $key = self::CART_RECOVERY): Arm
    {
        $experiment = $this->experiment($key);
        $arm = self::armFor($experiment->salt, $experiment->holdout_bps, $subjectHash);

        try {
            DB::transaction(fn () => ExperimentAssignment::create([
                'experiment_id' => $experiment->id,
                'subject_hash' => $subjectHash,
                'arm' => $arm->value,
            ]));
        } catch (UniqueConstraintViolationException) {
            // Already assigned; the stored arm is the truth if the split ever changed.
            $stored = ExperimentAssignment::query()
                ->where('experiment_id', $experiment->id)
                ->where('subject_hash', $subjectHash)
                ->value('arm');

            return Arm::from((string) $stored);
        }

        return $arm;
    }

    public static function armFor(string $salt, int $holdoutBps, string $subjectHash): Arm
    {
        $bucket = hexdec(substr(hash_hmac('sha256', $subjectHash, $salt), 0, 8)) % 10_000;

        return $bucket < $holdoutBps ? Arm::Control : Arm::Treatment;
    }

    /**
     * Change the split for future customers. Locked during a Proof Pilot.
     */
    public function setHoldout(int $holdoutBps, ?int $userId, string $key = self::CART_RECOVERY): void
    {
        $experiment = $this->experiment($key);

        if ($experiment->holdout_locked) {
            throw new InvalidArgumentException('The holdout is mandatory during the pilot and cannot be changed.');
        }

        if ($holdoutBps < 500) {
            throw new InvalidArgumentException('The holdout can go down to 5% after the pilot, never to zero (04 §4.4).');
        }

        $experiment->update(['holdout_bps' => $holdoutBps]);

        $this->audit->record('experiment.holdout_changed', ActorType::User, $userId, subject: $experiment, context: ['holdout_bps' => $holdoutBps]);
    }
}
