<?php

namespace App\Modules\Inbox;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Inbox\Data\ConversationSnapshot;
use App\Modules\Inbox\Events\AiPauseChanged;
use App\Modules\Inbox\Models\Conversation;
use App\Modules\Inbox\Models\ConversationNote;
use App\Shared\Phone\PhoneNumber;
use Carbon\CarbonImmutable;

/**
 * Public API of M04: the agent reads conversations and hands off through
 * it; data-subject requests and retention too.
 */
class Inbox
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function conversation(int $id): ?ConversationSnapshot
    {
        $conversation = Conversation::query()->find($id);

        return $conversation === null ? null : self::snapshot($conversation);
    }

    public function conversationFor(string $subjectHash): ?ConversationSnapshot
    {
        $conversation = Conversation::query()->where('subject_hash', $subjectHash)->first();

        return $conversation === null ? null : self::snapshot($conversation);
    }

    /** The AI stops and a person takes it, with the reason shown in the inbox (US-E11-03). */
    public function handOff(int $conversationId, string $reason): void
    {
        Conversation::query()->whereKey($conversationId)->update(['status' => 'open', 'reason' => substr($reason, 0, 30)]);
    }

    /**
     * Takeover (US-E12-03): from now on the AI writes nothing here, until a
     * member hands it back. Both are audited.
     */
    public function setAiPaused(int $conversationId, bool $paused, int $userId): ConversationSnapshot
    {
        $conversation = Conversation::query()->findOrFail($conversationId);

        $conversation->update($paused
            ? ['ai_paused' => true, 'ai_paused_by' => $userId, 'ai_paused_at' => CarbonImmutable::now()]
            : ['ai_paused' => false, 'ai_paused_by' => null, 'ai_paused_at' => null]);

        $this->audit->record($paused ? 'inbox.ai_taken_over' : 'inbox.ai_handed_back', ActorType::User, $userId, subject: $conversation);

        AiPauseChanged::dispatch($conversation->tenant_id, $conversation->id, $paused);

        return self::snapshot($conversation);
    }

    private static function snapshot(Conversation $conversation): ConversationSnapshot
    {
        return new ConversationSnapshot(
            id: $conversation->id,
            subjectHash: $conversation->subject_hash,
            phone: PhoneNumber::tryParse($conversation->phone),
            status: $conversation->status,
            reason: $conversation->reason,
            aiPaused: (bool) $conversation->ai_paused,
            lastInboundAt: $conversation->last_inbound_at,
        );
    }

    public function forgetSubject(string $subjectHash): int
    {
        $ids = Conversation::query()->where('subject_hash', $subjectHash)->pluck('id');

        ConversationNote::query()->whereIn('conversation_id', $ids)->delete();

        return Conversation::query()->whereIn('id', $ids)->delete();
    }

    public function purgeNotesBefore(CarbonImmutable $cutoff): int
    {
        return ConversationNote::query()->where('created_at', '<', $cutoff)->delete();
    }
}
