<?php

namespace App\Modules\Knowledge\Listeners;

use App\Modules\Commerce\Events\ProductChanged;
use App\Modules\Knowledge\Jobs\IndexProduct;

/**
 * A product change reaches the index within one queue hop (US-E09-05, ≤15 min).
 */
class FollowCatalog
{
    public function handle(ProductChanged $event): void
    {
        IndexProduct::dispatch($event->tenantId, $event->externalId)->afterCommit();
    }
}
