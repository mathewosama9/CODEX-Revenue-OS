<?php

namespace App\Modules\Commerce;

use App\Modules\Commerce\Models\StoreConnection;
use InvalidArgumentException;

/**
 * The adapter for a store's own platform (ADR-011): Salla, or WooCommerce
 * for trials. The map lives in config('codex.commerce_adapters').
 */
final class CommerceAdapters
{
    public function for(StoreConnection $store): CommerceAdapter
    {
        $class = config('codex.commerce_adapters.'.$store->provider);

        if (! is_string($class) || $class === '') {
            throw new InvalidArgumentException("No commerce adapter for provider '{$store->provider}'.");
        }

        $adapter = app($class);

        if (! $adapter instanceof CommerceAdapter) {
            throw new InvalidArgumentException("{$class} is not a CommerceAdapter.");
        }

        return $adapter;
    }
}
