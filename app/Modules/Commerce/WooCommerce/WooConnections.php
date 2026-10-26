<?php

namespace App\Modules\Commerce\WooCommerce;

use App\Models\Team;
use App\Modules\Commerce\Models\Cart;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Product;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\PlatformRateLimited;
use App\Modules\Commerce\StoreAlreadyConnected;
use App\Modules\Commerce\StoreConnections;
use App\Modules\Commerce\StoreStatus;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Money\Currencies;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Tenancy\WorkspaceLocale;
use Illuminate\Support\Str;
use SensitiveParameter;
use Throwable;

/**
 * Connecting a WooCommerce site from inside the owner's workspace (protocol
 * §1): check the read keys and the currency, create the connection, and hand
 * back the Store ID and connector secret once.
 *
 * The store sets the workspace currency and timezone (ADR-012) while the
 * workspace has no commerce data; after that a store in another currency is
 * refused, so amounts never mix.
 */
class WooConnections
{
    /**
     * Store ids are random, so the site itself is what may belong to one
     * workspace only: tenant_routes maps the normalized address (sha256,
     * to fit the column) to it.
     */
    public const SITE_ROUTE = 'woocommerce_site';

    public static function siteKey(string $normalizedUrl): string
    {
        return hash('sha256', $normalizedUrl);
    }

    public function __construct(
        private readonly WooClient $client,
        private readonly StoreConnections $connections,
        private readonly TenantContext $tenants,
        private readonly AuditLogger $audit,
        private readonly WorkspaceLocale $locale,
    ) {}

    /**
     * `https://Shop.Example/store/` → `https://shop.example/store`. Null for
     * anything that is not a public HTTPS site address.
     */
    public static function normalizeSiteUrl(string $url): ?string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || ! isset($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return null;
        }

        $host = strtolower(rtrim($parts['host'], '.'));

        if (! self::isPublicHost($host)) {
            return null;
        }

        $port = isset($parts['port']) && $parts['port'] !== 443 ? ':'.$parts['port'] : '';

        return "https://{$host}{$port}".rtrim($parts['path'] ?? '', '/');
    }

    /**
     * @return array{store_id: string, connector_secret: string}
     *
     * @throws WooConnectFailed
     */
    public function connect(
        string $siteUrl,
        #[SensitiveParameter] string $consumerKey,
        #[SensitiveParameter] string $consumerSecret,
        ?int $actorId = null,
    ): array {
        $tenantId = $this->tenants->require();
        $site = new WooSite($siteUrl, $consumerKey, $consumerSecret);

        $currency = $this->currency($site);

        if (! Currencies::supports($currency)) {
            throw new WooConnectFailed(WooConnectFailed::CURRENCY, [
                'currency' => $currency === '' ? '?' : $currency,
                'supported' => implode(', ', Currencies::codes()),
            ]);
        }

        $owner = TenantRoute::tenantFor(self::SITE_ROUTE, self::siteKey($siteUrl));

        if ($owner !== null && $owner !== $tenantId && $this->tenants->run($owner, fn () => $this->activeStore() !== null)) {
            throw new WooConnectFailed(WooConnectFailed::OTHER_WORKSPACE);
        }

        // Before the connection: its backfill reads amounts in the new currency.
        $this->adoptStoreLocale($tenantId, $currency, $this->timezone($site));

        // Reconnecting keeps the Store ID, so the plugin settings stay valid except the secret.
        $storeId = StoreConnection::query()->where('provider', TenantRoute::WOOCOMMERCE)->value('external_store_id')
            ?? 'woo_'.strtolower((string) Str::ulid());
        $connectorSecret = bin2hex(random_bytes(32));

        try {
            $this->connections->connect(TenantRoute::WOOCOMMERCE, $storeId, [
                'site_url' => $siteUrl,
                'credentials' => [
                    'consumer_key' => $consumerKey,
                    'consumer_secret' => $consumerSecret,
                    'connector_secret' => $connectorSecret,
                ],
            ], $actorId);
        } catch (StoreAlreadyConnected $exception) {
            throw new WooConnectFailed($exception->reason === StoreAlreadyConnected::OTHER_PLATFORM
                ? WooConnectFailed::OTHER_PLATFORM
                : WooConnectFailed::OTHER_WORKSPACE);
        }

        TenantRoute::query()->where('provider', self::SITE_ROUTE)->where('tenant_id', $tenantId)->delete();
        TenantRoute::bind(self::SITE_ROUTE, self::siteKey($siteUrl), $tenantId);

        return ['store_id' => $storeId, 'connector_secret' => $connectorSecret];
    }

    /**
     * A new connector secret; the old one stops working at once.
     *
     * @return array{store_id: string, connector_secret: string}|null null without an active store
     */
    public function rotateSecret(?int $actorId = null): ?array
    {
        $store = $this->activeStore();

        if ($store === null) {
            return null;
        }

        $secret = bin2hex(random_bytes(32));
        $store->update(['credentials' => ['connector_secret' => $secret] + ($store->credentials ?? [])]);

        $this->audit->record('store.connector_secret_rotated', $actorId === null ? ActorType::System : ActorType::User, $actorId, subject: $store);

        return ['store_id' => $store->external_store_id, 'connector_secret' => $secret];
    }

    public function disconnect(?int $actorId = null): void
    {
        $this->connections->disconnect(TenantRoute::WOOCOMMERCE, $actorId);

        TenantRoute::query()->where('provider', self::SITE_ROUTE)->where('tenant_id', $this->tenants->require())->delete();
    }

    public function activeStore(): ?StoreConnection
    {
        return StoreConnection::query()
            ->where('provider', TenantRoute::WOOCOMMERCE)
            ->where('status', StoreStatus::Active)
            ->first();
    }

    /**
     * A workspace without products, carts or orders takes the store's
     * currency and timezone. One with data keeps its own: a store in another
     * currency is refused, and the timezone stays (report days, quiet hours).
     *
     * @throws WooConnectFailed
     */
    private function adoptStoreLocale(int $tenantId, string $currency, ?string $timezone): void
    {
        // A live store on another platform (Salla, SAR): the connect below refuses
        // this one, so the workspace must not take its currency first.
        if (StoreConnection::query()->where('provider', '!=', TenantRoute::WOOCOMMERCE)->where('status', StoreStatus::Active)->exists()) {
            return;
        }

        $hasData = Product::query()->exists() || Cart::query()->exists() || Order::query()->exists();
        $workspaceCurrency = $this->locale->currency($tenantId)->code;

        if ($hasData) {
            if ($workspaceCurrency !== $currency) {
                throw new WooConnectFailed(WooConnectFailed::CURRENCY_MISMATCH, [
                    'currency' => $currency,
                    'workspace_currency' => $workspaceCurrency,
                ]);
            }

            return;
        }

        Team::query()->whereKey($tenantId)->update(array_filter([
            'currency' => $currency,
            'timezone' => $timezone,
        ]));

        $this->locale->forget($tenantId);
    }

    /**
     * The site's timezone from the public WordPress index (`timezone_string`,
     * else a whole-hour `gmt_offset`). Null keeps the workspace's: the
     * connect never fails over it.
     */
    private function timezone(WooSite $site): ?string
    {
        try {
            $index = $this->client->index($site);
        } catch (Throwable) {
            return null;
        }

        return self::phpTimezone($index['timezone_string'] ?? null, $index['gmt_offset'] ?? null);
    }

    /** "Africa/Cairo" as is; offset 2 → "Etc/GMT-2" (POSIX sign), 0 → "UTC"; anything else null. */
    public static function phpTimezone(mixed $name, mixed $gmtOffset): ?string
    {
        if (is_string($name) && $name !== '' && in_array($name, timezone_identifiers_list(), true)) {
            return $name;
        }

        if (! is_numeric($gmtOffset) || (float) $gmtOffset !== floor((float) $gmtOffset) || abs((float) $gmtOffset) > 14) {
            return null;
        }

        $hours = (int) $gmtOffset;

        return $hours === 0 ? 'UTC' : 'Etc/GMT'.($hours > 0 ? '-' : '+').abs($hours);
    }

    /**
     * The keys work and the store currency, from `system_status` (needs an
     * admin's key) or, failing that, any product read plus the currency.
     *
     * @throws WooConnectFailed
     */
    private function currency(WooSite $site): string
    {
        try {
            $currency = $this->client->get($site, 'system_status')['settings']['currency'] ?? null;

            if (is_string($currency) && $currency !== '') {
                return strtoupper($currency);
            }
        } catch (WooRequestFailed $exception) {
            if ($exception->status === WooRequestFailed::UNREACHABLE || ($exception->status >= 300 && $exception->status < 400)) {
                throw $this->failure($exception);
            }
        } catch (PlatformRateLimited) {
            throw new WooConnectFailed(WooConnectFailed::UNREACHABLE);
        }

        try {
            $this->client->get($site, 'products', ['per_page' => 1]);
            $currency = $this->client->get($site, 'data/currencies/current')['code'] ?? null;
        } catch (WooRequestFailed $exception) {
            throw $this->failure($exception);
        } catch (PlatformRateLimited) {
            throw new WooConnectFailed(WooConnectFailed::UNREACHABLE);
        }

        return is_string($currency) ? strtoupper($currency) : '';
    }

    private function failure(WooRequestFailed $exception): WooConnectFailed
    {
        return new WooConnectFailed(match (true) {
            $exception->status === WooRequestFailed::UNREACHABLE => WooConnectFailed::UNREACHABLE,
            $exception->status >= 300 && $exception->status < 400 => WooConnectFailed::REDIRECTED,
            in_array($exception->status, [401, 403], true) => WooConnectFailed::BAD_KEYS,
            in_array($exception->status, [200, 404], true) => WooConnectFailed::NOT_WOOCOMMERCE,
            default => WooConnectFailed::UNREACHABLE,
        });
    }

    /**
     * No loopback, private or reserved addresses, and no local names. A name
     * is checked again when it is resolved (WooHostResolver, on every request)
     * and the HTTP client never follows redirects.
     */
    private static function isPublicHost(string $host): bool
    {
        $ip = trim($host, '[]');

        if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
            return WooHostResolver::isPublicIp($ip);
        }

        if ($host === 'localhost' || ! str_contains($host, '.') || preg_match('/\.(localhost|local|internal|lan|home|test)$/', $host) === 1) {
            return false;
        }

        // "127.1", "0177.0.0.1" or "0x7f.1" are IPs to the resolver, not names.
        if (preg_match('/(^|\.)(\d+|0x[0-9a-f]+)$/', $host) === 1) {
            return false;
        }

        return preg_match('/^[a-z0-9.-]+$/', $host) === 1;
    }
}
