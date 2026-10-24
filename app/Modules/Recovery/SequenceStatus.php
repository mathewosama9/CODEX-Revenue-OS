<?php

namespace App\Modules\Recovery;

enum SequenceStatus: string
{
    /** Treatment: messages are scheduled. */
    case Active = 'active';

    /** Control: would have been sent; nothing is sent (exposure logged). */
    case Holdout = 'holdout';

    /** Passed consent/eligibility, but not worth the message cost (same rule for both arms). */
    case Suppressed = 'suppressed';

    case Ineligible = 'ineligible';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
