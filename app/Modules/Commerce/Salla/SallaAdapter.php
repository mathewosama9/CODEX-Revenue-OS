<?php

namespace App\Modules\Commerce\Salla;

use App\Modules\Commerce\CommerceAdapter;
use App\Modules\Commerce\Data\CartData;
use App\Modules\Commerce\Data\TokenSet;
use App\Modules\Commerce\Models\StoreConnection;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\RequestException;

/**
 * [افتراض] Endpoint paths and query names follow docs.salla.dev (2026-10-21);
 * confirm each one on the demo store (P0b spike) before the first pilot.
 */
class SallaAdapter implements CommerceAdapter
{
    public function __construct(
        private readonly SallaClient $client,
        private readonly SallaPayloadMapper $mapper,
    ) {}

    public function abandonedCarts(StoreConnection $store, int $sinceMinutes): iterable
    {
        foreach ($this->client->paginate($store, 'carts/abandoned', ['offset' => $sinceMinutes, 'per_page' => 60]) as $row) {
            yield $this->mapper->cart($row);
        }
    }

    public function abandonedCart(StoreConnection $store, string $externalId): ?CartData
    {
        try {
            $body = $this->client->get($store, "carts/abandoned/{$externalId}");
        } catch (RequestException $exception) {
            if ($exception->response->status() === 404) {
                return null;
            }

            throw $exception;
        }

        return is_array($body['data'] ?? null) ? $this->mapper->cart($body['data']) : null;
    }

    public function ordersSince(StoreConnection $store, CarbonImmutable $since): iterable
    {
        $query = ['from_date' => $since->setTimezone('Asia/Riyadh')->toDateString(), 'per_page' => 60];

        foreach ($this->client->paginate($store, 'orders', $query) as $row) {
            $order = $this->mapper->order($row);

            if ($order->placedAt->greaterThanOrEqualTo($since)) {
                yield $order;
            }
        }
    }

    public function products(StoreConnection $store): iterable
    {
        foreach ($this->client->paginate($store, 'products', ['per_page' => 60]) as $row) {
            yield $this->mapper->product($row);
        }
    }

    public function categories(StoreConnection $store): iterable
    {
        foreach ($this->client->paginate($store, 'categories') as $row) {
            yield ['external_id' => (string) $row['id'], 'name' => (string) ($row['name'] ?? '')];
        }
    }

    public function storeProfile(StoreConnection $store): array
    {
        $data = (array) ($this->client->userInfo($store)['data'] ?? []);

        return [
            'name' => data_get($data, 'merchant.name'),
            'email' => data_get($data, 'email'),
        ];
    }

    public function refreshTokens(StoreConnection $store): TokenSet
    {
        return SallaTokens::fromResponse($this->client->refreshToken((string) $store->refresh_token));
    }
}
