<?php

namespace App\Modules\Compliance;

use App\Modules\Commerce\Commerce;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Modules\Inbox\Inbox;
use App\Modules\Messaging\Messaging;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Phone\SubjectHasher;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Webhooks\WebhookEvent;
use App\Shared\Webhooks\WebhookStatus;

/**
 * Export or delete one customer's data at the merchant's request (PDPL rights,
 * 09 §9.15.2). Deletion also revokes marketing consent, so the customer is
 * never contacted again; the consent and audit trail themselves are kept as
 * proof that the request was honoured.
 */
class DataSubjectRequests
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly SubjectHasher $hasher,
        private readonly Commerce $commerce,
        private readonly Messaging $messaging,
        private readonly Inbox $inbox,
        private readonly ConsentGate $consent,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function export(PhoneNumber $phone, ?int $userId): array
    {
        $hash = $this->hasher->hash($this->tenants->require(), $phone);

        $this->audit->record('data_request.exported', ActorType::User, $userId);

        return $this->commerce->exportSubject($hash) + [
            'messages' => $this->messaging->conversation($hash, 500),
            'marketing_consent' => $this->consent->mayMessage($phone) ? 'granted' : 'not_granted',
        ];
    }

    /**
     * @return array<string, int>
     */
    public function delete(PhoneNumber $phone, ?int $userId): array
    {
        $hash = $this->hasher->hash($this->tenants->require(), $phone);

        $this->consent->revoke($phone, ConsentSource::MerchantRequest, ['request' => 'data_deletion']);

        $result = [
            'customers' => $this->commerce->forgetSubject($hash),
            'messages' => $this->messaging->forgetSubject($hash),
            'conversations' => $this->inbox->forgetSubject($hash),
            'webhook_payloads' => $this->redactWebhookPayloads($phone),
        ];

        $this->audit->record('data_request.deleted', ActorType::User, $userId, context: $result);

        return $result;
    }

    /**
     * Raw webhook payloads (kept for retries and debugging) may still hold the
     * phone, name and address. Payloads are encrypted, so each of this tenant's
     * is read and, if it mentions the number, replaced. The row stays, marked
     * processed, so a platform retry is still recognised as a duplicate and the
     * customer is not re-created (review H5).
     */
    private function redactWebhookPayloads(PhoneNumber $phone): int
    {
        // The national number (last 9 digits) matches every way platforms write it:
        // +966551234567, 966551234567, 0551234567, or 551234567 next to a country code.
        $needle = substr(ltrim($phone->e164, '+'), -9);
        $redacted = 0;

        WebhookEvent::query()
            ->where('tenant_id', $this->tenants->require())
            ->chunkById(500, function ($events) use ($needle, &$redacted) {
                foreach ($events as $event) {
                    if (! str_contains((string) json_encode($event->payload), $needle)) {
                        continue;
                    }

                    $event->update([
                        'payload' => ['redacted' => 'data_subject_request'],
                        'status' => WebhookStatus::Processed,
                        'processed_at' => $event->processed_at ?? now(),
                        'error' => null,
                    ]);
                    $redacted++;
                }
            });

        return $redacted;
    }
}
