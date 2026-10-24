<?php

namespace App\Modules\Inbox\Listeners;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;
use App\Modules\Inbox\Models\Conversation;
use App\Modules\Inbox\Notifications\CustomerNeedsHuman;
use App\Modules\Messaging\Events\InboundMessageReceived;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

/**
 * Any customer message (except an opt-out, already handled) lands in the
 * handoff queue within seconds (US-E12-05).
 */
class OpenConversation
{
    public function __construct(private readonly TenantContext $tenants) {}

    public function handle(InboundMessageReceived $event): void
    {
        if ($event->subjectHash === null || $event->isOptOut) {
            return;
        }

        $this->tenants->run($event->tenantId, function () use ($event) {
            $conversation = Conversation::query()->firstOrNew(['subject_hash' => $event->subjectHash]);
            $wasWaiting = $conversation->exists && $conversation->status !== 'closed';

            $conversation->fill([
                'status' => 'open',
                'reason' => $event->isAmbiguous ? 'not_now' : 'customer_message',
                'last_message_at' => CarbonImmutable::now(),
            ])->save();

            if (! $wasWaiting) {
                Notification::send($this->inboxUsers($event->tenantId), new CustomerNeedsHuman($conversation->reason));
            }
        });
    }

    /**
     * @return list<User>
     */
    private function inboxUsers(int $tenantId): array
    {
        $team = Team::query()->find($tenantId);

        if ($team === null) {
            return [];
        }

        return array_values($team->members->filter(
            fn (User $user) => $user->hasTeamPermission($team, TeamPermission::UseInbox),
        )->all());
    }
}
