<?php

namespace App\Modules\Inbox\Data;

use App\Shared\Phone\PhoneNumber;
use Carbon\CarbonImmutable;

final readonly class ConversationSnapshot
{
    public function __construct(
        public int $id,
        public string $subjectHash,
        public ?PhoneNumber $phone,
        public string $status,
        public string $reason,
        public bool $aiPaused,
        public ?CarbonImmutable $lastInboundAt,
    ) {}

    /**
     * Meta's customer service window: free-form replies only within 24 hours
     * of the customer's last message.
     */
    public function windowOpen(CarbonImmutable $at): bool
    {
        return $this->lastInboundAt !== null && $this->lastInboundAt->greaterThan($at->subHours(24));
    }
}
