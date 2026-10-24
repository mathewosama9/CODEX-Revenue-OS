<?php

namespace App\Modules\Inbox;

use App\Modules\Inbox\Models\Conversation;
use App\Modules\Inbox\Models\ConversationNote;
use Carbon\CarbonImmutable;

/**
 * Public API of M04 for data-subject requests and retention.
 */
class Inbox
{
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
