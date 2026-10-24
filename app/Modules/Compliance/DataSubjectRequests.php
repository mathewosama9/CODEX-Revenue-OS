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
        ];

        $this->audit->record('data_request.deleted', ActorType::User, $userId, context: $result);

        return $result;
    }
}
