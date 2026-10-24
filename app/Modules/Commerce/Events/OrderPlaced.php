<?php

namespace App\Modules\Commerce\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Order outcome for Recovery (cancel pending sends) and Measurement (outcomes).
 */
final class OrderPlaced
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $orderId,
    ) {}
}
