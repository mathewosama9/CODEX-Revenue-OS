<?php

namespace App\Modules\Messaging;

enum MessageStatus: string
{
    /** Recorded before calling Meta, so a crash can never cause a second send. */
    case Sending = 'sending';

    case Accepted = 'accepted';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';

    /** The call may or may not have reached Meta. Never retried (at-most-once, ADR-010). */
    case Unknown = 'unknown';

    case Received = 'received';

    public function rank(): int
    {
        return match ($this) {
            self::Sending, self::Unknown, self::Received => 0,
            self::Accepted => 1,
            self::Sent => 2,
            self::Delivered => 3,
            self::Read => 4,
            self::Failed => 5,
        };
    }
}
