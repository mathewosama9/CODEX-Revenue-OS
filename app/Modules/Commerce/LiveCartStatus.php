<?php

namespace App\Modules\Commerce;

/**
 * What the platform says about a cart right before we message its customer.
 */
enum LiveCartStatus: string
{
    case Abandoned = 'abandoned';

    case Purchased = 'purchased';

    /** No longer listed as abandoned (expired or deleted), not proof of a purchase. */
    case Gone = 'gone';

    /** The platform could not be reached (rate limit, outage, timeout). Ask again later. */
    case Unknown = 'unknown';
}
