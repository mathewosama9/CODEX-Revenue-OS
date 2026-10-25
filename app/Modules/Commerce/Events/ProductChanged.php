<?php

namespace App\Modules\Commerce\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * A product was created, updated, hidden or deleted. The search index
 * follows (US-E09-05: a removed product leaves the index).
 */
final class ProductChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $tenantId,
        public readonly string $externalId,
    ) {}
}
