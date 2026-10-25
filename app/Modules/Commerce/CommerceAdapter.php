<?php

namespace App\Modules\Commerce;

use App\Modules\Commerce\Data\CartData;
use App\Modules\Commerce\Data\OrderData;
use App\Modules\Commerce\Data\PageWindow;
use App\Modules\Commerce\Data\ProductData;
use App\Modules\Commerce\Data\TokenSet;
use App\Modules\Commerce\Models\StoreConnection;
use Carbon\CarbonImmutable;

/**
 * The only door to a commerce platform (ADR-011). Domain code speaks our
 * language; each platform (Salla now, Zid in P4) maps to it in its adapter.
 */
interface CommerceAdapter
{
    /**
     * @return iterable<CartData>
     */
    public function abandonedCarts(StoreConnection $store, int $sinceMinutes, ?PageWindow $window = null): iterable;

    /** Null when the platform no longer has it as an abandoned cart. */
    public function abandonedCart(StoreConnection $store, string $externalId): ?CartData;

    /**
     * @return iterable<OrderData>
     */
    public function ordersSince(StoreConnection $store, CarbonImmutable $since, ?PageWindow $window = null): iterable;

    /**
     * @return iterable<ProductData>
     */
    public function products(StoreConnection $store, ?PageWindow $window = null): iterable;

    /**
     * @return iterable<array{external_id: string, name: string}>
     */
    public function categories(StoreConnection $store, ?PageWindow $window = null): iterable;

    /**
     * @return array{name: string|null, email: string|null}
     */
    public function storeProfile(StoreConnection $store): array;

    /** Single use on Salla: never call twice for the same token (lock first). */
    public function refreshTokens(StoreConnection $store): TokenSet;
}
