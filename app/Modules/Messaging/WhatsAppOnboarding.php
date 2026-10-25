<?php

namespace App\Modules\Messaging;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Messaging\Meta\MetaGraphClient;
use App\Modules\Messaging\Models\WhatsAppAccount;
use App\Modules\Messaging\Models\WhatsAppNumber;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Tenancy\TenantContext;
use Throwable;

/**
 * Embedded Signup v4 completion (US-E03-01): the WABA and portfolio belong to
 * the merchant, Meta fees go on the merchant's card, we hold a business token.
 */
class WhatsAppOnboarding
{
    public function __construct(
        private readonly MetaGraphClient $meta,
        private readonly TenantContext $tenants,
        private readonly TemplateRegistry $templates,
        private readonly AuditLogger $audit,
    ) {}

    public function complete(string $code, string $wabaId, string $phoneNumberId, string $pin, ?int $userId): WhatsAppNumber
    {
        // ADR-006: the storage region is irreversible once a number is registered.
        $region = config('services.meta.data_localization_region');

        if (! is_string($region) || $region === '') {
            throw new WhatsAppSignupFailed(WhatsAppSignupFailed::REGION_NOT_SET, 'ADR-006: set META_DATA_LOCALIZATION_REGION before registering the first number.');
        }

        $existing = TenantRoute::tenantFor(TenantRoute::META, $wabaId);

        if ($existing !== null && $existing !== $this->tenants->require()) {
            throw new WhatsAppSignupFailed(WhatsAppSignupFailed::ALREADY_CONNECTED, 'This WhatsApp Business Account is already connected to another workspace.');
        }

        $token = $this->step(WhatsAppSignupFailed::CODE_REJECTED, fn () => $this->meta->exchangeCode($code));
        $this->step(WhatsAppSignupFailed::META_ERROR, fn () => $this->meta->subscribeApp($token, $wabaId));
        $this->step(WhatsAppSignupFailed::REGISTRATION_FAILED, fn () => $this->meta->registerNumber($token, $phoneNumberId, $pin, $region));
        $info = $this->step(WhatsAppSignupFailed::META_ERROR, fn () => $this->meta->number($token, $phoneNumberId));

        $account = WhatsAppAccount::query()->updateOrCreate(['waba_id' => $wabaId], [
            'access_token' => $token,
            'status' => 'active',
        ]);

        $number = WhatsAppNumber::query()->updateOrCreate(['phone_number_id' => $phoneNumberId], [
            'whatsapp_account_id' => $account->id,
            'display_phone_number' => $info['display_phone_number'] ?? null,
            'quality_rating' => QualityRating::tryFrom(strtoupper((string) ($info['quality_rating'] ?? ''))) ?? QualityRating::Unknown,
            'status' => 'active',
        ]);

        TenantRoute::bind(TenantRoute::META, $wabaId, $this->tenants->require());

        $this->audit->record('whatsapp.connected', ActorType::User, $userId, subject: $account);

        $this->templates->sync($account);

        return $number;
    }

    /**
     * Runs one call to Meta and names the step that failed. Meta's own error
     * text stays in the exception chain for our logs, never in the response.
     *
     * @template T
     *
     * @param  callable(): T  $call
     * @return T
     */
    private function step(string $reason, callable $call): mixed
    {
        try {
            return $call();
        } catch (WhatsAppSignupFailed $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new WhatsAppSignupFailed($reason, 'Embedded Signup failed at: '.$reason, $exception);
        }
    }
}
