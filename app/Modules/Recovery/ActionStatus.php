<?php

namespace App\Modules\Recovery;

/**
 * State machine of a scheduled action (09 §9.9.1).
 */
enum ActionStatus: string
{
    case Pending = 'pending';
    case Claimed = 'claimed';
    case AwaitingApproval = 'awaiting_approval';
    case Done = 'done';
    case Skipped = 'skipped';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    /** @return list<self> */
    public static function open(): array
    {
        return [self::Pending, self::Claimed, self::AwaitingApproval];
    }
}
