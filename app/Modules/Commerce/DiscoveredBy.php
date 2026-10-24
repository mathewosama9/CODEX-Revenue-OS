<?php

namespace App\Modules\Commerce;

enum DiscoveredBy: string
{
    case Webhook = 'webhook';

    /** Found by polling with no webhook: counts as a reconciliation gap. */
    case Reconciliation = 'reconciliation';

    case Backfill = 'backfill';
}
