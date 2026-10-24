<?php

namespace App\Modules\Compliance\Consent;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Compliance\Models\ConsentRecord;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Phone\SubjectHasher;
use App\Shared\Tenancy\TenantContext;
use InvalidArgumentException;

/**
 * The single question every sender asks before a marketing message:
 * "may we message this person?" (09 §9.4.1, US-E04-01).
 *
 * Safe failure: no record, an invalid number or a revoked consent all mean no.
 */
class ConsentGate
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly SubjectHasher $hasher,
        private readonly AuditLogger $audit,
    ) {}

    public function mayMessage(
        ?PhoneNumber $phone,
        ConsentChannel $channel = ConsentChannel::WhatsApp,
        ConsentPurpose $purpose = ConsentPurpose::Marketing,
    ): bool {
        if ($phone === null) {
            return false;
        }

        $latest = ConsentRecord::query()
            ->where('subject_hash', $this->subjectHash($phone))
            ->where('channel', $channel)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        return $latest?->status === ConsentStatus::Granted;
    }

    /**
     * @param  array<string, mixed>  $evidence  What the customer agreed to and where it is proven
     *                                          (text version, checkout reference). No PII.
     */
    public function grant(
        PhoneNumber $phone,
        ConsentSource $source,
        array $evidence,
        ConsentChannel $channel = ConsentChannel::WhatsApp,
        ConsentPurpose $purpose = ConsentPurpose::Marketing,
    ): void {
        if ($source === ConsentSource::OptOutKeyword || $evidence === []) {
            throw new InvalidArgumentException('A consent grant needs a grant source and documented evidence.');
        }

        $this->append($phone, ConsentStatus::Granted, $source, $evidence, $channel, $purpose);
    }

    /**
     * @param  array<string, mixed>  $evidence
     */
    public function revoke(
        PhoneNumber $phone,
        ConsentSource $source,
        array $evidence = [],
        ConsentChannel $channel = ConsentChannel::WhatsApp,
        ConsentPurpose $purpose = ConsentPurpose::Marketing,
    ): void {
        $record = $this->append($phone, ConsentStatus::Revoked, $source, $evidence, $channel, $purpose);

        ConsentRevoked::dispatch($record->tenant_id, $record->subject_hash, $channel, $purpose);
    }

    /**
     * @param  array<string, mixed>  $evidence
     */
    private function append(
        PhoneNumber $phone,
        ConsentStatus $status,
        ConsentSource $source,
        array $evidence,
        ConsentChannel $channel,
        ConsentPurpose $purpose,
    ): ConsentRecord {
        $record = ConsentRecord::create([
            'subject_hash' => $this->subjectHash($phone),
            'channel' => $channel,
            'purpose' => $purpose,
            'status' => $status,
            'source' => $source,
            'evidence' => $evidence,
        ]);

        $this->audit->record(
            "consent.{$status->value}",
            ActorType::System,
            subject: $record,
            context: ['source' => $source->value, 'channel' => $channel->value],
        );

        return $record;
    }

    private function subjectHash(PhoneNumber $phone): string
    {
        return $this->hasher->hash($this->tenants->require(), $phone);
    }
}
