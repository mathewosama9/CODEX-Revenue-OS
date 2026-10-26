<?php

namespace App\Modules\Messaging;

use App\Modules\Messaging\Data\SendOutcome;
use App\Modules\Messaging\Data\SendResult;
use App\Modules\Messaging\Meta\MetaGraphClient;
use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageTemplate;
use App\Modules\Messaging\Models\WhatsAppNumber;
use App\Shared\Phone\CallingCode;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Phone\SubjectHasher;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Sends approved marketing templates for the current tenant (US-E03-02).
 *
 * At-most-once (ADR-010): the row is written as `sending` before Meta is
 * called; a repeated idempotency key never sends again, and a call whose
 * outcome is unknown is never retried — a lost message is cheaper than a
 * duplicate (spam reports, quality rating).
 */
class MessageSender
{
    public const PER_USER_LIMIT_CODE = '131049';

    public function __construct(
        private readonly MetaGraphClient $meta,
        private readonly TenantContext $tenants,
        private readonly SubjectHasher $hasher,
    ) {}

    /**
     * @param  list<string>  $bodyParameters  {{1}}, {{2}}… values for the template body
     * @param  array<string, mixed>  $context  ids only (cart id, step), never PII
     */
    public function sendTemplate(
        PhoneNumber $to,
        string $purpose,
        array $bodyParameters,
        ?string $buttonUrlSuffix,
        string $idempotencyKey,
        array $context = [],
    ): SendResult {
        $number = $this->activeNumber();
        $template = MessageTemplate::query()
            ->where('purpose', $purpose)
            ->where('status', 'APPROVED')
            ->where('category', 'MARKETING')
            ->first();

        if ($number === null || $template === null) {
            return new SendResult(SendOutcome::NotReady);
        }

        try {
            $message = DB::transaction(fn () => Message::create([
                'whatsapp_number_id' => $number->id,
                'direction' => Message::OUTBOUND,
                'idempotency_key' => $idempotencyKey,
                'subject_hash' => $this->hasher->hash($this->tenants->require(), $to),
                'template_name' => $template->name,
                'category' => 'marketing',
                'status' => MessageStatus::Sending,
                'context' => $this->withRecipientCountry($context, $to),
            ]));
        } catch (UniqueConstraintViolationException) {
            return $this->previousOutcome($idempotencyKey);
        }

        try {
            $response = $this->meta->sendMarketingTemplate(
                $number->account->access_token,
                $number->phone_number_id,
                $to->e164,
                $this->templatePayload($template, $bodyParameters, $buttonUrlSuffix),
            );
        } catch (Throwable) {
            $message->update(['status' => MessageStatus::Unknown]);

            return new SendResult(SendOutcome::Unknown, $message->id);
        }

        if ($response->successful() && is_string($wamid = data_get($response->json(), 'messages.0.id'))) {
            $message->update(['status' => MessageStatus::Accepted, 'wamid' => $wamid, 'sent_at' => now()]);

            return new SendResult(SendOutcome::Accepted, $message->id);
        }

        $code = (string) data_get($response->json(), 'error.code', $response->status());

        $message->update([
            'status' => MessageStatus::Failed,
            'error_code' => $code,
            'error_title' => mb_substr((string) data_get($response->json(), 'error.message', ''), 0, 250),
            'failed_at' => now(),
        ]);

        return new SendResult(
            $code === self::PER_USER_LIMIT_CODE ? SendOutcome::PerUserLimit : SendOutcome::Rejected,
            $message->id,
            $code,
        );
    }

    /**
     * The key was used before. Report what really happened to that send, so a
     * failed attempt is never mistaken for a delivered message.
     */
    private function previousOutcome(string $idempotencyKey): SendResult
    {
        $previous = Message::query()->where('idempotency_key', $idempotencyKey)->first();

        return match ($previous?->status) {
            MessageStatus::Failed => new SendResult(
                $previous->error_code === self::PER_USER_LIMIT_CODE ? SendOutcome::PerUserLimit : SendOutcome::Rejected,
                $previous->id,
                $previous->error_code,
            ),
            MessageStatus::Unknown => new SendResult(SendOutcome::Unknown, $previous->id),
            default => new SendResult(SendOutcome::Duplicate, $previous?->id),
        };
    }

    /**
     * Free-form reply inside the service window (opt-out confirmation, P1).
     *
     * @param  array<string, mixed>  $context
     */
    public function sendServiceText(PhoneNumber $to, string $text, array $context = []): SendResult
    {
        $number = $this->activeNumber(requireMarketingQuality: false);

        if ($number === null) {
            return new SendResult(SendOutcome::NotReady, null, 'no_active_number');
        }

        $message = Message::create([
            'whatsapp_number_id' => $number->id,
            'direction' => Message::OUTBOUND,
            'subject_hash' => $this->hasher->hash($this->tenants->require(), $to),
            'category' => 'service',
            'status' => MessageStatus::Sending,
            'body' => $text,
            'context' => $this->withRecipientCountry($context, $to),
        ]);

        try {
            $response = $this->meta->sendText($number->account->access_token, $number->phone_number_id, $to->e164, $text);
            $wamid = data_get($response->json(), 'messages.0.id');
            $accepted = $response->successful() && is_string($wamid);
            $message->update($accepted
                ? ['status' => MessageStatus::Accepted, 'wamid' => $wamid, 'sent_at' => now()]
                : ['status' => MessageStatus::Failed, 'error_code' => (string) data_get($response->json(), 'error.code', $response->status()), 'failed_at' => now()]);

            return $accepted
                ? new SendResult(SendOutcome::Accepted, $message->id)
                : new SendResult(SendOutcome::Rejected, $message->id, $message->error_code);
        } catch (Throwable) {
            $message->update(['status' => MessageStatus::Unknown]);

            return new SendResult(SendOutcome::Unknown, $message->id);
        }
    }

    private function activeNumber(bool $requireMarketingQuality = true): ?WhatsAppNumber
    {
        $number = WhatsAppNumber::query()->with('account')->where('status', 'active')->first();

        if ($number === null || $number->account->status !== 'active') {
            return null;
        }

        return ! $requireMarketingQuality || $number->quality_rating->allowsMarketing() ? $number : null;
    }

    /**
     * @param  list<string>  $bodyParameters
     * @return array<string, mixed>
     */
    private function templatePayload(MessageTemplate $template, array $bodyParameters, ?string $buttonUrlSuffix): array
    {
        $components = [];

        if ($bodyParameters !== []) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(fn (string $value) => ['type' => 'text', 'text' => $value], $bodyParameters),
            ];
        }

        if ($buttonUrlSuffix !== null) {
            $components[] = [
                'type' => 'button',
                'sub_type' => 'url',
                'index' => '0',
                'parameters' => [['type' => 'text', 'text' => $buttonUrlSuffix]],
            ];
        }

        return ['name' => $template->name, 'language' => ['code' => $template->language], 'components' => $components];
    }

    /**
     * The recipient's country, not the number: Meta prices a delivered
     * message by it (CostLedger, ADR-012).
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function withRecipientCountry(array $context, PhoneNumber $to): array
    {
        return $context + array_filter(['recipient_country' => CallingCode::country($to)]);
    }
}
