<?php

namespace App\Modules\Commerce\WooCommerce;

use RuntimeException;

/** Why a WooCommerce site could not be connected; the API maps the reason to text. */
class WooConnectFailed extends RuntimeException
{
    public const UNREACHABLE = 'unreachable';

    public const REDIRECTED = 'redirected';

    public const BAD_KEYS = 'bad_keys';

    public const NOT_WOOCOMMERCE = 'not_woocommerce';

    public const CURRENCY = 'currency';

    public const OTHER_WORKSPACE = 'other_workspace';

    public const OTHER_PLATFORM = 'other_platform';

    public function __construct(public readonly string $reason)
    {
        parent::__construct("WooCommerce connect failed: {$reason}.");
    }
}
