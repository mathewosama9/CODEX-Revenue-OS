<?php

namespace App\Modules\Compliance\Consent;

enum OptOutDecision: string
{
    /** Revoke consent and cancel every pending send now. */
    case OptOut = 'opt_out';

    /** Pause the current sequence and flag for a human; consent stays. */
    case Ambiguous = 'ambiguous';

    case None = 'none';
}
