<?php

namespace App\Modules\Commerce\WooCommerce;

use App\Modules\Commerce\Data\PageWindow;
use App\Modules\Commerce\PlatformRateLimited;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP client for one WooCommerce site: the REST API v3 with read-only
 * keys, and the CODEX Connector plugin's signed endpoints (protocol §2, §4).
 *
 * HTTPS only and no redirects (a redirect could leave HTTPS or the site).
 * The site must resolve to public addresses, and the request is pinned to
 * the address that was checked (WooHostResolver: SSRF and DNS rebinding).
 * Basic auth first; some hosts strip the Authorization header, so a 401 is
 * retried once with the keys as query parameters (still HTTPS, as WooCommerce
 * documents) and the site is remembered for a day.
 */
class WooClient
{
    private const TIMEOUT_SECONDS = 20;

    private const CONNECT_TIMEOUT_SECONDS = 10;

    /** Woo's largest page. */
    public const PER_PAGE = 100;

    /** Bounds for a site's Retry-After: never a hot retry loop, never parked for a day. */
    private const MIN_RETRY_AFTER = 5;

    private const MAX_RETRY_AFTER = 3600;

    public function __construct(private readonly WooHostResolver $resolver) {}

    /**
     * @param  array<string, scalar>  $query
     * @return array<string, mixed>
     */
    public function get(WooSite $site, string $path, array $query = []): array
    {
        return $this->decode($this->wc($site, $path, $query));
    }

    /**
     * Walk the pages of a WooCommerce list endpoint (`X-WP-TotalPages`): all
     * of them, or one window that can be resumed from `$window->next`.
     *
     * @param  array<string, scalar>  $query
     * @return iterable<array<string, mixed>>
     */
    public function paginate(WooSite $site, string $path, array $query = [], ?PageWindow $window = null): iterable
    {
        $window ??= new PageWindow;
        $last = $window->from + $window->pages - 1;

        for ($page = $window->from; $page <= $last; $page++) {
            $response = $this->wc($site, $path, $query + ['per_page' => self::PER_PAGE, 'page' => $page]);
            $rows = $this->decode($response);

            foreach ($rows as $row) {
                if (is_array($row)) {
                    yield $row;
                }
            }

            $totalPages = (int) ($response->header('X-WP-TotalPages') ?: 1);

            if ($page >= $totalPages || $rows === []) {
                $window->next = null;

                return;
            }
        }

        $window->next = $last + 1;
    }

    /**
     * The public WordPress index (`GET /wp-json`): site name and namespaces.
     *
     * @return array<string, mixed>
     */
    public function index(WooSite $site): array
    {
        return $this->decode($this->send(fn () => $this->http($site)->get($site->restUrl())));
    }

    /**
     * A signed GET to the plugin (protocol §2): `$route` without `/wp-json`,
     * e.g. `/codex/v1/carts`.
     *
     * @param  array<string, scalar>  $query
     * @return array<string, mixed>
     */
    public function plugin(WooSite $site, string $route, array $query = []): array
    {
        if ($site->connectorSecret === '') {
            throw new WooRequestFailed(401, 'The store has no connector secret.');
        }

        $timestamp = now()->getTimestamp();
        $queryString = WooSignature::query($query);
        $url = $site->restUrl($route).($queryString === '' ? '' : '?'.$queryString);

        return $this->decode($this->send(fn () => $this->http($site)
            ->withHeaders([
                'X-Codex-Timestamp' => (string) $timestamp,
                'X-Codex-Signature' => WooSignature::forGet($site->connectorSecret, $timestamp, $route, $query),
            ])
            ->get($url)));
    }

    /**
     * The plugin's cart list: `{ "carts": [...], "next_page": n|null }`.
     *
     * @param  array<string, scalar>  $query
     * @return iterable<array<string, mixed>>
     */
    public function pluginPages(WooSite $site, string $route, array $query = [], ?PageWindow $window = null): iterable
    {
        $window ??= new PageWindow;
        $last = $window->from + $window->pages - 1;

        for ($page = $window->from; $page <= $last; $page++) {
            $body = $this->plugin($site, $route, $query + ['page' => $page]);

            foreach ((array) ($body['carts'] ?? []) as $row) {
                if (is_array($row)) {
                    yield $row;
                }
            }

            $next = $body['next_page'] ?? null;

            if (! is_numeric($next) || (int) $next <= $page) {
                $window->next = null;

                return;
            }
        }

        $window->next = $last + 1;
    }

    /**
     * @param  array<string, scalar>  $query
     */
    private function wc(WooSite $site, string $path, array $query): Response
    {
        $url = $site->restUrl('wc/v3/'.ltrim($path, '/'));
        $cacheKey = "woocommerce:query-auth:{$site->key()}";
        $queryAuth = Cache::get($cacheKey) === true;

        if (! $queryAuth) {
            $response = $this->send(fn () => $this->http($site)->withBasicAuth($site->consumerKey, $site->consumerSecret)->get($url, $query));

            if ($response->status() !== 401) {
                return $response;
            }
        }

        $response = $this->send(fn () => $this->http($site)->get($url, $query + [
            'consumer_key' => $site->consumerKey,
            'consumer_secret' => $site->consumerSecret,
        ]));

        if (! $queryAuth && $response->successful()) {
            Cache::put($cacheKey, true, now()->addDay());
        }

        return $response;
    }

    /**
     * @throws WooRequestFailed when the site does not resolve to public addresses
     */
    private function http(WooSite $site): PendingRequest
    {
        $host = (string) parse_url($site->url, PHP_URL_HOST);
        $port = (int) (parse_url($site->url, PHP_URL_PORT) ?: 443);
        $addresses = $this->resolver->publicAddresses($host);

        if ($addresses === []) {
            throw new WooRequestFailed(WooRequestFailed::UNREACHABLE, 'The WooCommerce site does not resolve to a public address.');
        }

        // An IP literal is already the checked address; a name is pinned to it.
        $pin = filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) === false
            ? ['curl' => [CURLOPT_RESOLVE => ["{$host}:{$port}:{$addresses[0]}"]]]
            : [];

        return Http::acceptJson()
            ->withOptions($pin)
            ->withoutRedirecting()
            ->timeout(self::TIMEOUT_SECONDS)
            ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
            ->retry(2, 500, fn ($exception) => $exception instanceof ConnectionException, throw: false);
    }

    /**
     * A connection error's message names the URL, which can hold the
     * consumer secret: replace it with one that does not.
     *
     * @param  callable(): Response  $request
     */
    private function send(callable $request): Response
    {
        try {
            return $request();
        } catch (ConnectionException) {
            throw WooRequestFailed::unreachable();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        if ($response->status() === 429) {
            // Retry-After may be an HTTP date, which reads as 0: fall back to a minute.
            $retryAfter = (int) $response->header('Retry-After');

            throw new PlatformRateLimited($retryAfter > 0 ? max(self::MIN_RETRY_AFTER, min(self::MAX_RETRY_AFTER, $retryAfter)) : 60, 'WooCommerce');
        }

        if (! $response->successful()) {
            throw new WooRequestFailed($response->status(), "WooCommerce answered HTTP {$response->status()}.");
        }

        $body = $response->json();

        if (! is_array($body)) {
            throw new WooRequestFailed($response->status(), 'WooCommerce did not answer with JSON.');
        }

        return $body;
    }
}
