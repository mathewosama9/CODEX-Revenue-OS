<?php

namespace App\Modules\Messaging\Listeners;

use App\Modules\Billing\UsageMeter;
use App\Modules\Measurement\CostLedger;
use App\Modules\Messaging\Events\MessageDelivered;
use App\Modules\Messaging\Models\Message;
use App\Shared\Tenancy\TenantContext;

/**
 * Hands Measurement the cost and the touch of a delivered message — ids,
 * hashes and amounts only, never content (ADR-009).
 */
class BookDeliveryCost
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly CostLedger $ledger,
        private readonly UsageMeter $usage,
    ) {}

    public function handle(MessageDelivered $event): void
    {
        $this->tenants->run($event->tenantId, function () use ($event) {
            $message = Message::query()->find($event->messageId);

            if ($message === null || $message->subject_hash === null || $message->delivered_at === null) {
                return;
            }

            $this->usage->increment(UsageMeter::MESSAGES_DELIVERED.':'.($event->pricingCategory ?? 'unknown'), at: $message->delivered_at);

            $this->ledger->recordMessageDelivered(
                "message:{$message->id}",
                $message->subject_hash,
                $event->pricingCategory,
                $event->billable,
                $message->delivered_at,
                is_string($country = $message->context['recipient_country'] ?? null) ? $country : null,
            );
        });
    }
}
