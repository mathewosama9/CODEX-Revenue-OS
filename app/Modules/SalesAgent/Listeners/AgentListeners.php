<?php

namespace App\Modules\SalesAgent\Listeners;

use App\Modules\Inbox\Events\AiPauseChanged;
use App\Modules\Inbox\Inbox;
use App\Modules\Messaging\Events\InboundMessageReceived;
use App\Modules\SalesAgent\Drafts;
use App\Modules\SalesAgent\Jobs\RespondToCustomer;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Events\Dispatcher;

/**
 * A customer message wakes the agent after the webhook commits; a takeover
 * voids its pending drafts.
 */
class AgentListeners
{
    /** Lets a burst of messages settle, so one run answers them together. [اقتراح] */
    public const DEBOUNCE_SECONDS = 4;

    public function __construct(
        private readonly TenantContext $tenants,
        private readonly Inbox $inbox,
        private readonly Drafts $drafts,
    ) {}

    public function onInbound(InboundMessageReceived $event): void
    {
        if ($event->subjectHash === null || $event->isOptOut) {
            return;
        }

        $conversation = $this->tenants->run($event->tenantId, fn () => $this->inbox->conversationFor((string) $event->subjectHash));

        if ($conversation === null || $conversation->aiPaused) {
            return;
        }

        RespondToCustomer::dispatch($event->tenantId, $conversation->id, $event->messageId)
            ->delay(now()->addSeconds(self::DEBOUNCE_SECONDS))
            ->afterCommit();
    }

    public function onPause(AiPauseChanged $event): void
    {
        if ($event->paused) {
            $this->tenants->run($event->tenantId, fn () => $this->drafts->supersedeFor($event->conversationId));
        }
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            InboundMessageReceived::class => 'onInbound',
            AiPauseChanged::class => 'onPause',
        ];
    }
}
