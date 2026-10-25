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

    /** At least one documented grant exists: the merchant's consent source works. */
    public function hasAnyGrant(): bool
    {
        return ConsentRecord::query()->where('status', ConsentStatus::Granted)->exists();
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
     * Grants for a merchant import, in bulk. A customer whose latest record is a
     * revocation (they said stop, or asked for deletion) is never re-granted by
     * an import: only the customer can opt back in.
     *
     * @param  list<PhoneNumber>  $phones
     * @param  array<string, mixed>  $evidence  Shared by every row: import id, text hash, attestation. No PII.
     * @return array{granted: int, already_granted: int, skipped_opted_out: int}
     */
    public function importGrants(array $phones, array $evidence): array
    {
        if ($evidence === []) {
            throw new InvalidArgumentException('A consent import needs documented evidence.');
        }

        $hashes = array_values(array_unique(array_map(fn (PhoneNumber $phone) => $this->subjectHash($phone), $phones)));
        $counts = ['granted' => 0, 'already_granted' => 0, 'skipped_opted_out' => 0];

        foreach (array_chunk($hashes, 1000) as $chunk) {
            $latest = ConsentRecord::query()
                ->whereIn('subject_hash', $chunk)
                ->where('channel', ConsentChannel::WhatsApp)
                ->where('purpose', ConsentPurpose::Marketing)
                ->orderBy('subject_hash')
                ->orderByDesc('id')
                ->get(['subject_hash', 'status'])
                ->unique('subject_hash')
                ->mapWithKeys(fn (ConsentRecord $record) => [$record->subject_hash => $record->status]);

            $rows = [];

            foreach ($chunk as $hash) {
                $status = $latest[$hash] ?? null;

                if ($status === ConsentStatus::Revoked) {
                    $counts['skipped_opted_out']++;
                } elseif ($status === ConsentStatus::Granted) {
                    $counts['already_granted']++;
                } else {
                    $rows[] = [
                        'tenant_id' => $this->tenants->require(),
                        'subject_hash' => $hash,
                        'channel' => ConsentChannel::WhatsApp->value,
                        'purpose' => ConsentPurpose::Marketing->value,
                        'status' => ConsentStatus::Granted->value,
                        'source' => ConsentSource::MerchantImport->value,
                        'evidence' => json_encode($evidence, JSON_THROW_ON_ERROR),
                    ];
                }
            }

            if ($rows !== []) {
                ConsentRecord::query()->insert($rows);
                $counts['granted'] += count($rows);
            }
        }

        $this->audit->record('consent.imported', ActorType::User, $evidence['attested_by'] ?? null, context: $counts + [
            'import' => $evidence['import'] ?? null,
        ]);

        return $counts;
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
