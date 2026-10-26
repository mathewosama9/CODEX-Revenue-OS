<?php

namespace App\Modules\SalesAgent;

use App\Modules\Compliance\Consent\ConsentGate;
use App\Shared\Phone\PhoneNumber;

/**
 * May the AI answer this customer? Founder decision 2026-10-25: a reply to a
 * customer who wrote first is a service conversation, so only an opt-out
 * silences it; marketing consent is not required. [افتراض قانوني: confirm
 * with the lawyer, ADR-008]. `codex.ai.reply_requires_marketing_consent`
 * brings back the stricter rule (documented consent needed).
 */
class ReplyConsent
{
    public function __construct(
        private readonly ConsentGate $consent,
        private readonly bool $requireMarketingConsent,
    ) {}

    /** Null when the AI may reply, else why not. */
    public function refusal(PhoneNumber $phone): ?string
    {
        return match (true) {
            $this->consent->hasOptedOut($phone) => 'opted_out',
            $this->requireMarketingConsent && ! $this->consent->mayMessage($phone) => 'no_consent',
            default => null,
        };
    }

    public function allows(PhoneNumber $phone): bool
    {
        return $this->refusal($phone) === null;
    }
}
