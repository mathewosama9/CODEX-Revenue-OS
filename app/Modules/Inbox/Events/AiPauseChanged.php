<?php

namespace App\Modules\Inbox\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A member took a conversation over from the AI, or handed it back.
 */
final class AiPauseChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $conversationId,
        public readonly bool $paused,
    ) {}
}
