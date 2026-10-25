<?php

namespace App\Modules\Commerce\WooCommerce;

use App\Modules\Commerce\CommerceAdapter;
use App\Modules\Commerce\Data\CartData;
use App\Modules\Commerce\Data\OrderData;
use App\Modules\Commerce\Data\PageWindow;
use App\Modules\Commerce\Data\ProductData;
use App\Modules\Commerce\Data\TokenSet;
use App\Modules\Commerce\Models\StoreConnection;
use Carbon\CarbonImmutable;
use LogicException;

/**
 * WordPress + WooCommerce (trials, docs/integrations/woocommerce-connector.md).
 * Catalog and orders come from the REST API v3; carts come from the CODEX
 * Connector plugin, since WooCommerce has no abandoned-cart API.
 */
class WooAdapter implements CommerceAdapter
{
    /** Plugin cart ids (protocol §5: ≤ 64 chars); also what the plugin's route accepts. */
    public const CART_ID = '/^[A-Za-z0-9_-]{1,64}$/';

    public function __construct(
        private readonly WooClient $client,
        private readonly WooPayloadMapper $mapper,
        private readonly WooCheckoutConsent $checkoutConsent,
    ) {}

    public function abandonedCarts(StoreConnection $store, int $sinceMinutes, ?PageWindow $window = null): iterable
    {
        $query = [
            'since' => CarbonImmutable::now()->utc()->subMinutes($sinceMinutes)->format('Y-m-d\TH:i:s\Z'),
            'per_page' => WooClient::PER_PAGE,
        ];

        foreach ($this->client->pluginPages(WooSite::fromStore($store), '/codex/v1/carts', $query, $window) as $row) {
            // Expired carts are not abandoned any more; purchased ones mark ours purchased.
            if (($row['status'] ?? null) !== 'expired' && is_string($row['id'] ?? null) && preg_match(self::CART_ID, $row['id']) === 1) {
                // Reconciliation may see the cart before its webhook: record the box first, like the webhook does.
                $this->checkoutConsent->record($row);
                yield $this->mapper->cart($row);
            }
        }
    }

    /**
     * Null on 404 or an expired cart. A purchased cart comes back with
     * `purchased`, so the caller can record the purchase (as with Salla).
     */
    public function abandonedCart(StoreConnection $store, string $externalId): ?CartData
    {
        if (preg_match(self::CART_ID, $externalId) !== 1) {
            return null;
        }

        try {
            $body = $this->client->plugin(WooSite::fromStore($store), "/codex/v1/carts/{$externalId}");
        } catch (WooRequestFailed $exception) {
            if ($exception->status === 404) {
                return null;
            }

            throw $exception;
        }

        if (($body['status'] ?? null) === 'expired' || ! isset($body['id'])) {
            return null;
        }

        return $this->mapper->cart($body);
    }

    public function ordersSince(StoreConnection $store, CarbonImmutable $since, ?PageWindow $window = null): iterable
    {
        $query = [
            'after' => $since->utc()->format('Y-m-d\TH:i:s'),
            'dates_are_gmt' => 'true',
            'orderby' => 'date',
            'order' => 'asc',
        ];

        foreach ($this->client->paginate(WooSite::fromStore($store), 'orders', $query, $window) as $row) {
            $order = $this->mapper->order($row);

            if ($order->placedAt->greaterThanOrEqualTo($since)) {
                yield $order;
            }
        }
    }

    public function products(StoreConnection $store, ?PageWindow $window = null): iterable
    {
        $site = WooSite::fromStore($store);

        foreach ($this->client->paginate($site, 'products', ['status' => 'publish'], $window) as $row) {
            yield $this->mapper->product($row, $this->variations($site, $row));
        }
    }

    public function categories(StoreConnection $store, ?PageWindow $window = null): iterable
    {
        foreach ($this->client->paginate(WooSite::fromStore($store), 'products/categories', [], $window) as $row) {
            yield [
                'external_id' => (string) $row['id'],
                'name' => html_entity_decode((string) ($row['name'] ?? ''), ENT_QUOTES | ENT_HTML5),
            ];
        }
    }

    /** The site name from the public WordPress index; Woo has no owner email for read keys. */
    public function storeProfile(StoreConnection $store): array
    {
        $name = $this->client->index(WooSite::fromStore($store))['name'] ?? null;

        return [
            'name' => is_string($name) && trim($name) !== '' ? html_entity_decode(trim($name), ENT_QUOTES | ENT_HTML5) : null,
            'email' => null,
        ];
    }

    public function refreshTokens(StoreConnection $store): TokenSet
    {
        throw new LogicException('WooCommerce REST keys do not expire: there is no token to refresh.');
    }

    /** One order, for `order.*` events (the plugin sends only the id). Null when Woo has none. */
    public function order(StoreConnection $store, string $externalId): ?OrderData
    {
        $row = $this->one(WooSite::fromStore($store), 'orders/'.rawurlencode($externalId));

        return $row === null ? null : $this->mapper->order($row);
    }

    /** One product with its variations, for `product.updated`. Null when Woo has none. */
    public function product(StoreConnection $store, string $externalId): ?ProductData
    {
        $site = WooSite::fromStore($store);
        $row = $this->one($site, 'products/'.rawurlencode($externalId));

        return $row === null ? null : $this->mapper->product($row, $this->variations($site, $row));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function one(WooSite $site, string $path): ?array
    {
        try {
            $row = $this->client->get($site, $path);
        } catch (WooRequestFailed $exception) {
            if ($exception->status === 404) {
                return null;
            }

            throw $exception;
        }

        return isset($row['id']) ? $row : null;
    }

    /**
     * @param  array<string, mixed>  $product
     * @return list<array<string, mixed>>
     */
    private function variations(WooSite $site, array $product): array
    {
        if (($product['type'] ?? null) !== 'variable' || ! isset($product['id'])) {
            return [];
        }

        $rows = [];

        foreach ($this->client->paginate($site, 'products/'.rawurlencode((string) $product['id']).'/variations') as $row) {
            $rows[] = $row;
        }

        return $rows;
    }
}
