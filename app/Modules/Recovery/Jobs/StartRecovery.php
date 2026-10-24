<?php

namespace App\Modules\Recovery\Jobs;

use App\Modules\Commerce\CartStatus;
use App\Modules\Commerce\Commerce;
use App\Modules\Commerce\Data\CartSnapshot;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\QuietHours;
use App\Modules\Measurement\Arm;
use App\Modules\Measurement\ExperimentAssigner;
use App\Modules\Measurement\ExposureLog;
use App\Modules\Measurement\MarginLookup;
use App\Modules\Messaging\Messaging;
use App\Modules\Recovery\ActionStatus;
use App\Modules\Recovery\Models\RecoverySequence;
use App\Modules\Recovery\Models\RecoverySettings;
use App\Modules\Recovery\Models\ScheduledAction;
use App\Modules\Recovery\SequenceStatus;
use App\Shared\Tenancy\TenantAware;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;

/**
 * Decides, once per abandoned cart, whether a recovery sequence starts
 * (06 S2). Every check before the exposure runs identically for both arms;
 * otherwise control would hold weaker carts and the uplift would look bigger
 * than it is (ADR-013).
 */
class StartRecovery implements ShouldQueue
{
    use Dispatchable, Queueable, TenantAware;

    /** Suppress a cart whose expected margin is under this multiple of the message cost. */
    private const VALUE_TO_COST_MULTIPLE = 5;

    private const MARKETING_MESSAGE_HALALAS = 22;

    public function __construct(public int $tenantId, public int $cartId) {}

    public function handle(
        Commerce $commerce,
        Messaging $messaging,
        ConsentGate $consent,
        ExperimentAssigner $assigner,
        ExposureLog $exposures,
        MarginLookup $margins,
        QuietHours $quietHours,
        AuditLogger $audit,
    ): void {
        if (RecoverySequence::query()->where('cart_id', $this->cartId)->exists()) {
            return;
        }

        $settings = RecoverySettings::current();
        $cart = $commerce->cart($this->cartId);

        if ($cart === null) {
            return;
        }

        $now = CarbonImmutable::now();

        $reason = match (true) {
            ! $settings->enabled => 'recovery_disabled',
            ! $commerce->isStoreActive() => 'store_not_active',
            ! $messaging->canSendMarketing('cart_recovery_1') => 'whatsapp_not_ready',
            $cart->status !== CartStatus::Abandoned => 'not_abandoned',
            $cart->abandonedAt !== null && $cart->abandonedAt->lt($now->subHours($settings->max_cart_age_hours)) => 'too_old',
            $cart->phone === null || $cart->phoneHash === null => 'no_valid_phone',
            ! $consent->mayMessage($cart->phone) => 'no_consent',
            default => null,
        };

        if ($reason !== null) {
            $this->record($cart, null, SequenceStatus::Ineligible, $reason, $audit);

            return;
        }

        /** @var string $subjectHash */
        $subjectHash = $cart->phoneHash;
        $arm = $assigner->arm($subjectHash);

        $reason = match (true) {
            $this->recentlySequenced($subjectHash, $settings->frequency_days) => 'frequency_cap',
            $cart->totalHalalas < $settings->min_cart_halalas => 'below_min_value',
            ! $this->worthTheCost($cart, $settings, $margins) => 'not_worth_cost',
            default => null,
        };

        if ($reason !== null) {
            $this->record($cart, $arm, $reason === 'frequency_cap' ? SequenceStatus::Ineligible : SequenceStatus::Suppressed, $reason, $audit);

            return;
        }

        DB::transaction(function () use ($cart, $arm, $subjectHash, $exposures, $now, $settings, $quietHours, $audit) {
            $exposures->record($cart->id, $subjectHash, $arm, $cart->totalHalalas, $now);

            if ($arm === Arm::Control) {
                $this->record($cart, $arm, SequenceStatus::Holdout, 'holdout', $audit);

                return;
            }

            $sequence = $this->record($cart, $arm, SequenceStatus::Active, null, $audit);

            if ($sequence === null) {
                return;
            }

            $due = ($cart->abandonedAt ?? $now)->addMinutes(max(60, $settings->first_delay_minutes));

            ScheduledAction::create([
                'recovery_sequence_id' => $sequence->id,
                'step' => 1,
                'status' => ActionStatus::Pending,
                'run_at' => $quietHours->nextAllowedAt($due->max($now)),
                'idempotency_key' => "recovery:{$cart->id}:1",
            ]);
        });
    }

    private function recentlySequenced(string $subjectHash, int $days): bool
    {
        return RecoverySequence::query()
            ->where('subject_hash', $subjectHash)
            ->whereIn('status', [SequenceStatus::Active, SequenceStatus::Holdout, SequenceStatus::Completed])
            ->where('started_at', '>=', CarbonImmutable::now()->subDays($days))
            ->exists();
    }

    /**
     * Rule-based scoring (spine §3.3 #1–2): is the cart's expected margin worth
     * the messages we would pay Meta for?
     */
    private function worthTheCost(CartSnapshot $cart, RecoverySettings $settings, MarginLookup $margins): bool
    {
        $marginHalalas = 0;

        foreach ($cart->items as $item) {
            $marginHalalas += $item->unitPriceHalalas * $item->quantity * $margins->marginBps($item->categoryExternalId) / 10_000;
        }

        if ($cart->items === []) {
            $marginHalalas = $cart->totalHalalas * $margins->marginBps(null) / 10_000;
        }

        return $marginHalalas >= $settings->max_messages * self::MARKETING_MESSAGE_HALALAS * self::VALUE_TO_COST_MULTIPLE;
    }

    private function record(CartSnapshot $cart, ?Arm $arm, SequenceStatus $status, ?string $reason, AuditLogger $audit): ?RecoverySequence
    {
        try {
            $sequence = DB::transaction(fn () => RecoverySequence::create([
                'cart_id' => $cart->id,
                'subject_hash' => $cart->phoneHash,
                'arm' => $arm?->value,
                'status' => $status,
                'reason' => $reason,
                'started_at' => CarbonImmutable::now(),
                'ended_at' => in_array($status, [SequenceStatus::Active], true) ? null : CarbonImmutable::now(),
            ]));
        } catch (UniqueConstraintViolationException) {
            return null;
        }

        $audit->record('recovery.decided', ActorType::System, subject: $sequence, context: [
            'cart_id' => $cart->id,
            'status' => $status->value,
            'reason' => $reason,
            'arm' => $arm?->value,
        ]);

        return $sequence;
    }
}
