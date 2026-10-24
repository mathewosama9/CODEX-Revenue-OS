<?php

namespace App\Modules\Messaging\Data;

enum SendOutcome: string
{
    case Accepted = 'accepted';

    /** Same idempotency key seen before: nothing was sent again. */
    case Duplicate = 'duplicate';

    /** Error 131049: Meta's per-user marketing limit. Retry no sooner than 24h. */
    case PerUserLimit = 'per_user_limit';

    /** Number not connected, quality RED, or no approved template for the purpose. */
    case NotReady = 'not_ready';

    case Rejected = 'rejected';

    /** Unknown whether Meta got it (timeout). Never retried automatically. */
    case Unknown = 'unknown';
}
