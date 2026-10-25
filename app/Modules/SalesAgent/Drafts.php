<?php

namespace App\Modules\SalesAgent;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Inbox\Inbox;
use App\Modules\Messaging\MessageSender;
use App\Modules\SalesAgent\Models\AgentRun;
use App\Modules\SalesAgent\Models\ReplyDraft;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Assisted mode (US-E11-01): a member approves, edits or rejects each AI
 * draft. Sending happens only here, after approval, inside the 24-hour
 * window and only if the customer has not opted out since.
 */
class Drafts
{
    public function __construct(
        private readonly Inbox $inbox,
        private readonly MessageSender $sender,
        private readonly ConsentGate $consent,
        private readonly AuditLogger $audit,
    ) {}

    public function approve(ReplyDraft $draft, int $userId, ?string $editedBody = null): ReplyDraft
    {
        $edited = $editedBody !== null && trim($editedBody) !== '' && trim($editedBody) !== trim($draft->body);
        $text = $edited ? trim((string) $editedBody) : $draft->body;

        // Claim it first: two members clicking at once send one message.
        $claimed = DB::table('reply_drafts')->where('id', $draft->id)->where('status', ReplyDraft::PENDING)
            ->update(['status' => 'sending', 'decided_by' => $userId, 'decided_at' => CarbonImmutable::now()]);

        if ($claimed === 0) {
            throw ValidationException::withMessages(['draft' => 'already_decided']);
        }

        $draft->refresh();
        $conversation = $this->inbox->conversation($draft->conversation_id);

        if ($conversation === null || $conversation->phone === null) {
            return $this->expire($draft, $userId, 'no_phone');
        }

        // Meta: free-form text only within 24 hours of the customer's last message.
        if (! $conversation->windowOpen(CarbonImmutable::now())) {
            return $this->expire($draft, $userId, 'window_closed');
        }

        if (! $this->consent->mayMessage($conversation->phone)) {
            return $this->expire($draft, $userId, 'no_consent');
        }

        $result = $this->sender->sendServiceText($conversation->phone, $text, ['source' => 'ai_draft', 'draft_id' => $draft->id]);

        $draft->update($result->accepted()
            ? ['status' => $edited ? 'edited' : 'approved', 'final_body' => $text]
            : ['status' => 'failed', 'send_error' => $result->outcome->value]);

        if ($result->accepted()) {
            AgentRun::query()->whereKey($draft->agent_run_id)->update(['status' => AgentRun::SENT]);
        }

        $this->audit->record($edited ? 'ai_draft.edited' : 'ai_draft.approved', ActorType::User, $userId, subject: $draft, context: [
            'outcome' => $result->outcome->value,
        ]);

        return $draft;
    }

    private function expire(ReplyDraft $draft, int $userId, string $reason): ReplyDraft
    {
        $draft->update(['status' => 'expired', 'send_error' => $reason]);
        $this->audit->record('ai_draft.expired', ActorType::User, $userId, subject: $draft, context: ['reason' => $reason]);

        return $draft;
    }

    public function reject(ReplyDraft $draft, int $userId): ReplyDraft
    {
        $claimed = DB::table('reply_drafts')->where('id', $draft->id)->where('status', ReplyDraft::PENDING)
            ->update(['status' => 'rejected', 'decided_by' => $userId, 'decided_at' => CarbonImmutable::now()]);

        if ($claimed === 0) {
            throw ValidationException::withMessages(['draft' => 'already_decided']);
        }

        $this->audit->record('ai_draft.rejected', ActorType::User, $userId, subject: $draft);

        return $draft->refresh();
    }

    /** Takeover: nothing the AI wrote earlier may go out now (US-E12-03). */
    public function supersedeFor(int $conversationId): int
    {
        return ReplyDraft::query()
            ->where('conversation_id', $conversationId)
            ->where('status', ReplyDraft::PENDING)
            ->update(['status' => 'superseded', 'decided_at' => CarbonImmutable::now()]);
    }
}
