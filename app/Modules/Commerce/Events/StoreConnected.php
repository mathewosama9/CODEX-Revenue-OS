<?php

namespace App\Modules\Commerce\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * The store's connection changed. Recovery stops on disconnect.
 */
final class StoreConnected
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly int $storeConnectionId,
    ) {}
}
