<?php

namespace App\Modules\Commerce\WooCommerce;

use App\Modules\Commerce\Models\StoreConnection;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * One WooCommerce site and its keys: the REST consumer key/secret (read only)
 * and the connector secret shared with the plugin. Never logged or serialized.
 */
final readonly class WooSite
{
    public function __construct(
        public string $url,
        #[SensitiveParameter] public string $consumerKey,
        #[SensitiveParameter] public string $consumerSecret,
        #[SensitiveParameter] public string $connectorSecret = '',
    ) {
        if (! str_starts_with(strtolower($url), 'https://')) {
            throw new InvalidArgumentException('WooCommerce sites are reached over HTTPS only.');
        }
    }

    public static function fromStore(StoreConnection $store): self
    {
        $credentials = $store->credentials ?? [];

        if (! is_string($store->site_url) || $store->site_url === '' || ($credentials['consumer_key'] ?? '') === '' || ($credentials['consumer_secret'] ?? '') === '') {
            throw new InvalidArgumentException('The WooCommerce store has no site address or REST keys.');
        }

        return new self(
            $store->site_url,
            (string) $credentials['consumer_key'],
            (string) $credentials['consumer_secret'],
            (string) ($credentials['connector_secret'] ?? ''),
        );
    }

    /** `{site}/wp-json/{path}`. */
    public function restUrl(string $path = ''): string
    {
        return rtrim($this->url, '/').'/wp-json/'.ltrim($path, '/');
    }

    /** A cache key for this site that carries no secret. */
    public function key(): string
    {
        return sha1(strtolower(rtrim($this->url, '/')));
    }

    /**
     * @return array<string, string>
     */
    public function __debugInfo(): array
    {
        return ['url' => $this->url];
    }
}
