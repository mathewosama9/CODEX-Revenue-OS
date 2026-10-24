<?php

namespace App\Modules\Commerce\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A cart became abandoned or was bought. Listened to by Recovery (M08) and Measurement (M09).
 */
final class CartAbandoned
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $cartId,
    ) {}
}
