<?php

namespace App\Modules\Commerce\Salla;

use App\Modules\Commerce\Models\StoreConnection;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP client for the Salla Admin API. Rate limits are per store and per
 * plan (Plus 120, Pro 360, Special 720 req/min); a 429 surfaces as
 * SallaRateLimited so the job can release itself for Retry-After seconds.
 */
class SallaClient
{
    public function __construct(
        private readonly string $apiBase,
        private readonly string $accountsBase,
        private readonly string $clientId,
        private readonly string $clientSecret,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function get(StoreConnection $store, string $path, array $query = []): array
    {
        $response = $this->request($store)->get(ltrim($path, '/'), $query);

        return $this->decode($response);
    }

    /**
     * Walk every page of a list endpoint.
     *
     * @param  array<string, mixed>  $query
     * @return iterable<array<string, mixed>>
     */
    public function paginate(StoreConnection $store, string $path, array $query = [], int $maxPages = 200): iterable
    {
        for ($page = 1; $page <= $maxPages; $page++) {
            $body = $this->get($store, $path, $query + ['page' => $page]);

            foreach ((array) ($body['data'] ?? []) as $row) {
                if (is_array($row)) {
                    yield $row;
                }
            }

            $totalPages = (int) data_get($body, 'pagination.totalPages', data_get($body, 'pagination.total_pages', 1));

            if ($page >= $totalPages) {
                return;
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function refreshToken(string $refreshToken): array
    {
        $response = Http::asForm()
            ->baseUrl($this->accountsBase)
            ->timeout(20)
            ->post('oauth2/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ]);

        return $this->decode($response);
    }

    /**
     * @return array<string, mixed>
     */
    public function userInfo(StoreConnection $store): array
    {
        $response = Http::baseUrl($this->accountsBase)
            ->withToken((string) $store->access_token)
            ->acceptJson()
            ->timeout(20)
            ->get('oauth2/user/info');

        return $this->decode($response);
    }

    private function request(StoreConnection $store): PendingRequest
    {
        return Http::baseUrl($this->apiBase)
            ->withToken((string) $store->access_token)
            ->acceptJson()
            ->timeout(20)
            ->retry(2, 500, fn ($exception) => $exception instanceof ConnectionException, throw: false);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        if ($response->status() === 429) {
            throw new SallaRateLimited((int) ($response->header('Retry-After') ?: 60));
        }

        if ($response->status() === 401) {
            throw new SallaUnauthorized('Salla rejected the token.');
        }

        $response->throw();

        return (array) $response->json();
    }
}
