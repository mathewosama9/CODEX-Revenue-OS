<?php

namespace App\Modules\Messaging\Meta;

use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Modules\Compliance\Consent\OptOutDecision;
use App\Modules\Compliance\Consent\OptOutDetector;
use App\Modules\Messaging\Events\InboundMessageReceived;
use App\Modules\Messaging\Events\MessageDelivered;
use App\Modules\Messaging\Events\MessageFailed;
use App\Modules\Messaging\Events\NumberQualityChanged;
use App\Modules\Messaging\MessageSender;
use App\Modules\Messaging\MessageStatus;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\WhatsAppNumber;
use App\Modules\Messaging\QualityRating;
use App\Modules\Messaging\TemplateRegistry;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Phone\SubjectHasher;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Webhooks\WebhookEvent;
use App\Shared\Webhooks\WebhookHandler;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Routes each `entry` by its WABA id to the tenant, then applies status
 * updates, inbound messages, template and quality changes. Every step is
 * idempotent: Meta duplicates arrive for days.
 */
class MetaWebhookHandler implements WebhookHandler
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly SubjectHasher $hasher,
        private readonly OptOutDetector $optOuts,
        private readonly ConsentGate $consent,
        private readonly MessageSender $sender,
        private readonly TemplateRegistry $templates,
    ) {}

    public function handle(WebhookEvent $event): void
    {
        foreach ((array) Arr::get($event->payload, 'entry', []) as $entry) {
            $wabaId = (string) Arr::get((array) $entry, 'id');
            $tenantId = TenantRoute::tenantFor(TenantRoute::META, $wabaId);

            if ($tenantId === null) {
                Log::info('Meta webhook for an unknown WABA ignored.', ['webhook_event_id' => $event->id]);

                continue;
            }

            $event->update(['tenant_id' => $tenantId]);

            $this->tenants->run($tenantId, function () use ($entry) {
                foreach ((array) Arr::get((array) $entry, 'changes', []) as $change) {
                    $this->change((string) Arr::get((array) $change, 'field'), (array) Arr::get((array) $change, 'value', []));
                }
            });
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function change(string $field, array $value): void
    {
        match ($field) {
            'messages' => $this->messages($value),
            'message_template_status_update' => $this->templates->updateStatus(
                (string) Arr::get($value, 'message_template_name'),
                (string) Arr::get($value, 'message_template_language'),
                (string) Arr::get($value, 'event'),
            ),
            'template_category_update' => $this->templates->updateCategory(
                (string) Arr::get($value, 'message_template_name'),
                (string) Arr::get($value, 'message_template_language'),
                (string) Arr::get($value, 'new_category'),
            ),
            'phone_number_quality_update' => $this->quality($value),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function messages(array $value): void
    {
        foreach ((array) Arr::get($value, 'statuses', []) as $status) {
            $this->status((array) $status);
        }

        $number = WhatsAppNumber::query()->where('phone_number_id', (string) Arr::get($value, 'metadata.phone_number_id'))->first();

        foreach ((array) Arr::get($value, 'messages', []) as $inbound) {
            $this->inbound((array) $inbound, $number);
        }
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function status(array $status): void
    {
        $message = Message::query()->where('wamid', (string) Arr::get($status, 'id'))->first();
        $next = MessageStatus::tryFrom((string) Arr::get($status, 'status'));

        if ($message === null || $next === null || $next->rank() <= $message->status->rank()) {
            return;
        }

        $at = CarbonImmutable::createFromTimestamp((int) Arr::get($status, 'timestamp', time()));
        $wasDelivered = $message->delivered_at !== null;

        $message->status = $next;
        $message->pricing_category = Arr::get($status, 'pricing.category') ?? $message->pricing_category;
        $message->billable = Arr::has($status, 'pricing.billable') ? (bool) Arr::get($status, 'pricing.billable') : $message->billable;

        match ($next) {
            MessageStatus::Sent => $message->sent_at ??= $at,
            MessageStatus::Delivered => $message->delivered_at ??= $at,
            MessageStatus::Read => [$message->delivered_at ??= $at, $message->read_at ??= $at],
            MessageStatus::Failed => [
                $message->failed_at = $at,
                $message->error_code = (string) Arr::get($status, 'errors.0.code'),
                $message->error_title = mb_substr((string) Arr::get($status, 'errors.0.title'), 0, 250),
            ],
            default => null,
        };

        $message->save();

        if (! $wasDelivered && $message->delivered_at !== null) {
            MessageDelivered::dispatch($message->tenant_id, $message->id, $message->pricing_category, $message->billable ?? true);
        }

        if ($next === MessageStatus::Failed) {
            MessageFailed::dispatch($message->tenant_id, $message->id, $message->error_code);
        }
    }

    /**
     * Opt-out is decided here, before the message reaches anyone (09 §9.8).
     *
     * @param  array<string, mixed>  $inbound
     */
    private function inbound(array $inbound, ?WhatsAppNumber $number): void
    {
        $wamid = (string) Arr::get($inbound, 'id');

        if ($wamid === '' || Message::query()->where('wamid', $wamid)->exists()) {
            return;
        }

        $phone = PhoneNumber::tryParse('+'.ltrim((string) Arr::get($inbound, 'from'), '+'));
        $text = $this->text($inbound);
        $isButton = in_array(Arr::get($inbound, 'type'), ['button', 'interactive'], true);
        $decision = $isButton && trim($text) === config('whatsapp_templates.opt_out_button_text')
            ? OptOutDecision::OptOut
            : $this->optOuts->detect($text);

        $optOut = $decision === OptOutDecision::OptOut && $phone !== null;

        // The message row is also the dedupe marker, so it commits together with
        // the consent revocation and the cancellations it triggers. A failure
        // part-way rolls everything back and the webhook retry starts clean,
        // instead of finding the wamid and skipping the opt-out (review B7).
        DB::transaction(function () use ($wamid, $number, $phone, $text, $inbound, $decision, $isButton, $optOut) {
            $message = Message::create([
                'whatsapp_number_id' => $number?->id,
                'direction' => Message::INBOUND,
                'wamid' => $wamid,
                'subject_hash' => $phone === null ? null : $this->hasher->hash($this->tenants->require(), $phone),
                'status' => MessageStatus::Received,
                'body' => $text,
                'context' => array_filter([
                    'type' => Arr::get($inbound, 'type'),
                    'reply_to_wamid' => Arr::get($inbound, 'context.id'),
                    'opt_out_decision' => $decision->value,
                ]),
            ]);

            if ($optOut) {
                $this->consent->revoke(
                    $phone,
                    $isButton ? ConsentSource::TemplateButton : ConsentSource::OptOutKeyword,
                    ['inbound_message_id' => $message->id],
                );
            }

            InboundMessageReceived::dispatch(
                $message->tenant_id,
                $message->id,
                $message->subject_hash,
                $decision === OptOutDecision::OptOut,
                $decision === OptOutDecision::Ambiguous,
                $phone?->e164,
            );
        });

        // Outside the transaction: a network call must never hold it open, and
        // the opt-out already stands even if this confirmation fails.
        if ($optOut) {
            $this->sender->sendServiceText($phone, (string) config('whatsapp_templates.opt_out_confirmation'), ['reason' => 'opt_out_confirmation']);
        }
    }

    /**
     * @param  array<string, mixed>  $inbound
     */
    private function text(array $inbound): string
    {
        return (string) (Arr::get($inbound, 'text.body')
            ?? Arr::get($inbound, 'button.text')
            ?? Arr::get($inbound, 'interactive.button_reply.title')
            ?? '');
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function quality(array $value): void
    {
        $rating = match (strtoupper((string) Arr::get($value, 'event'))) {
            'FLAGGED' => QualityRating::Red,
            'UNFLAGGED' => QualityRating::Green,
            default => null,
        };

        if ($rating === null) {
            return;
        }

        $display = preg_replace('/\D+/', '', (string) Arr::get($value, 'display_phone_number'));

        foreach (WhatsAppNumber::query()->get() as $number) {
            if (preg_replace('/\D+/', '', (string) $number->display_phone_number) === $display) {
                $number->update(['quality_rating' => $rating]);
                NumberQualityChanged::dispatch($number->tenant_id, $number->id, $rating->value);
            }
        }
    }
}
