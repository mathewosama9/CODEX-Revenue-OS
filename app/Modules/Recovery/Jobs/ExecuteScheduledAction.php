<?php

namespace App\Modules\Recovery\Jobs;

use App\Models\Team;
use App\Modules\Commerce\Commerce;
use App\Modules\Commerce\Data\CartSnapshot;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\QuietHours;
use App\Modules\Messaging\Data\SendOutcome;
use App\Modules\Messaging\MessageSender;
use App\Modules\Messaging\Messaging;
use App\Modules\Recovery\ActionStatus;
use App\Modules\Recovery\DiscountDecision;
use App\Modules\Recovery\DiscountPolicy;
use App\Modules\Recovery\Models\DiscountApproval;
use App\Modules\Recovery\Models\RecoverySettings;
use App\Modules\Recovery\Models\ScheduledAction;
use App\Modules\Recovery\RecoveryLifecycle;
use App\Shared\Tenancy\TenantAware;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Runs one due step of a recovery sequence. Re-checks everything at send time
 * (09 §9.9.1 — "the most important line in the design"), because an hour is
 * long enough for a purchase, an opt-out or a RED quality rating.
 *
 * Safe failure: when unsure, don't send.
 */
class ExecuteScheduledAction implements ShouldQueue
{
    use Dispatchable, Queueable, TenantAware;

    public int $tries = 1;

    private const NOT_READY_RETRY_MINUTES = 30;

    private const NOT_READY_MAX_ATTEMPTS = 3;

    private const PER_USER_LIMIT_MAX_ATTEMPTS = 2;

    public function __construct(public int $tenantId, public int $actionId) {}

    public function handle(
        Commerce $commerce,
        Messaging $messaging,
        ConsentGate $consent,
        QuietHours $quietHours,
        DiscountPolicy $discounts,
        MessageSender $sender,
        RecoveryLifecycle $lifecycle,
        AuditLogger $audit,
    ): void {
        $action = ScheduledAction::query()->with('sequence')->find($this->actionId);

        if ($action === null || ! in_array($action->status, [ActionStatus::Claimed, ActionStatus::AwaitingApproval], true)) {
            return;
        }

        $settings = RecoverySettings::current();
        $cart = $commerce->cart($action->sequence->cart_id);
        $now = CarbonImmutable::now();

        $stop = match (true) {
            ! $settings->enabled => 'recovery_paused',
            $cart === null || $cart->phone === null => 'cart_missing',
            ! $commerce->isStoreActive() => 'store_not_active',
            ! $consent->mayMessage($cart->phone) => 'no_consent',
            default => null,
        };

        if ($stop !== null) {
            $lifecycle->cancelSequence($action->sequence, $stop);

            return;
        }

        /** @var CartSnapshot $cart */
        if ($quietHours->isQuiet($now)) {
            $action->update(['status' => ActionStatus::Pending, 'run_at' => $quietHours->nextAllowedAt($now), 'claimed_at' => null]);

            return;
        }

        if (! $commerce->isStillAbandoned($cart->id)) {
            $lifecycle->cancelSequence($action->sequence, 'purchased');

            return;
        }

        $discount = $this->discount($action, $settings, $cart, $discounts, $now);

        if ($discount === null) {
            return; // Waiting for the owner's approval.
        }

        $purpose = $this->purpose($action->step, $discount, $messaging);

        if (! $messaging->canSendMarketing($purpose)) {
            $this->retryOrFail($action, 'whatsapp_not_ready', self::NOT_READY_RETRY_MINUTES, self::NOT_READY_MAX_ATTEMPTS, $lifecycle);

            return;
        }

        $storeName = (string) Team::query()->whereKey($this->tenantId)->value('name');
        $useDiscount = $purpose === 'cart_recovery_2_discount';
        $parameters = $useDiscount
            ? [$storeName, (string) $discount->code, (string) $cart->checkoutUrl]
            : [$storeName, (string) $cart->checkoutUrl];

        $result = $sender->sendTemplate($cart->phone, $purpose, $parameters, null, $action->idempotency_key, [
            'cart_id' => $cart->id,
            'step' => $action->step,
            'discount_percent' => $useDiscount ? $discount->percent : 0,
        ]);

        $audit->record('recovery.message_'.$result->outcome->value, ActorType::System, subject: $action, context: [
            'cart_id' => $cart->id,
            'step' => $action->step,
            'purpose' => $purpose,
            'discount_percent' => $useDiscount ? $discount->percent : 0,
            'discount_policy' => $discount->reason ?? $discount->kind,
        ]);

        match ($result->outcome) {
            SendOutcome::Accepted, SendOutcome::Duplicate => $this->sent($action, $settings, $quietHours, $lifecycle, $useDiscount ? $discount->percent : 0, $result->messageId),
            SendOutcome::PerUserLimit => $this->retryOrFail($action, 'per_user_limit_131049', 24 * 60, self::PER_USER_LIMIT_MAX_ATTEMPTS, $lifecycle),
            SendOutcome::NotReady => $this->retryOrFail($action, 'whatsapp_not_ready', self::NOT_READY_RETRY_MINUTES, self::NOT_READY_MAX_ATTEMPTS, $lifecycle),
            SendOutcome::Rejected, SendOutcome::Unknown => $lifecycle->failAction($action, 'send_'.$result->outcome->value),
        };
    }

    /**
     * @return DiscountDecision|null null while an approval is still open
     */
    private function discount(ScheduledAction $action, RecoverySettings $settings, CartSnapshot $cart, DiscountPolicy $discounts, CarbonImmutable $now): ?DiscountDecision
    {
        $approvalId = $action->result['approval_id'] ?? null;

        if (is_int($approvalId)) {
            $approval = DiscountApproval::query()->find($approvalId);

            if ($approval?->status === 'approved' && $settings->discount_code !== null) {
                return DiscountDecision::apply($approval->percent, $settings->discount_code);
            }

            if ($approval?->status === 'pending' && $approval->expires_at->isFuture()) {
                $action->update(['status' => ActionStatus::AwaitingApproval, 'run_at' => $approval->expires_at, 'claimed_at' => null]);

                return null;
            }

            if ($approval?->status === 'pending') {
                $approval->update(['status' => 'expired', 'decided_at' => $now]);
            }

            // Rejected or expired: the message still goes out, without a discount (06 S2).
            return DiscountDecision::none('approval_'.($approval->status ?? 'missing'));
        }

        $decision = $discounts->decide($settings, $cart, $action->step);

        if (! $decision->requiresApproval()) {
            return $decision;
        }

        $approval = DiscountApproval::create([
            'scheduled_action_id' => $action->id,
            'percent' => $decision->percent,
            'reason' => (string) $decision->reason,
            'status' => 'pending',
            'expires_at' => $now->addMinutes($settings->approval_window_minutes),
        ]);

        $action->update([
            'status' => ActionStatus::AwaitingApproval,
            'run_at' => $approval->expires_at,
            'claimed_at' => null,
            'result' => $action->result + ['approval_id' => $approval->id],
        ]);

        return null;
    }

    private function purpose(int $step, DiscountDecision $discount, Messaging $messaging): string
    {
        if ($step <= 1) {
            return 'cart_recovery_1';
        }

        return $discount->applies() && $messaging->canSendMarketing('cart_recovery_2_discount')
            ? 'cart_recovery_2_discount'
            : 'cart_recovery_2';
    }

    private function sent(ScheduledAction $action, RecoverySettings $settings, QuietHours $quietHours, RecoveryLifecycle $lifecycle, int $discountPercent, ?int $messageId): void
    {
        $action->update([
            'status' => ActionStatus::Done,
            'result' => $action->result + ['message_id' => $messageId, 'discount_percent' => $discountPercent],
        ]);

        if ($action->step >= min(3, $settings->max_messages)) {
            $lifecycle->completeSequence($action->sequence, 'all_steps_sent');

            return;
        }

        $next = $action->step + 1;

        ScheduledAction::create([
            'recovery_sequence_id' => $action->recovery_sequence_id,
            'step' => $next,
            'status' => ActionStatus::Pending,
            'run_at' => $quietHours->nextAllowedAt(CarbonImmutable::now()->addMinutes($settings->second_delay_minutes)),
            'idempotency_key' => "recovery:{$action->sequence->cart_id}:{$next}",
        ]);
    }

    private function retryOrFail(ScheduledAction $action, string $reason, int $minutes, int $maxAttempts, RecoveryLifecycle $lifecycle): void
    {
        if ($action->attempts + 1 >= $maxAttempts) {
            $lifecycle->failAction($action, $reason);

            return;
        }

        $action->update([
            'status' => ActionStatus::Pending,
            'attempts' => $action->attempts + 1,
            'last_error' => $reason,
            'run_at' => CarbonImmutable::now()->addMinutes($minutes),
            'claimed_at' => null,
        ]);
    }
}
