<?php

namespace App\Modules\Commerce;

enum StoreStatus: string
{
    case Active = 'active';

    /** Token refresh failed: the merchant must reconnect. Sending stops. */
    case NeedsReauth = 'needs_reauth';

    case Uninstalled = 'uninstalled';
}
