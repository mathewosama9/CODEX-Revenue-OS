<?php

namespace App\Modules\Commerce\WooCommerce;

/**
 * HMAC-SHA256 signing shared with the CODEX Connector plugin, both directions
 * (docs/integrations/woocommerce-connector.md §2). One secret per store.
 */
final class WooSignature
{
    /** Allowed clock difference between the site and us, in seconds. */
    public const WINDOW_SECONDS = 300;

    /** Plugin → us: `"{timestamp}.{raw_body}"`. */
    public static function forBody(string $secret, int $timestamp, string $body): string
    {
        return self::sign($secret, "{$timestamp}.{$body}");
    }

    /**
     * Us → plugin: `"{timestamp}.GET.{route}?{query}"`, route without `/wp-json`.
     *
     * @param  array<string, scalar>  $query
     */
    public static function forGet(string $secret, int $timestamp, string $route, array $query = []): string
    {
        return self::sign($secret, "{$timestamp}.GET.{$route}?".self::query($query));
    }

    /**
     * Query parameters sorted by key, RFC 3986 encoded: the plugin rebuilds
     * the same string from WP_REST_Request::get_query_params().
     *
     * @param  array<string, scalar>  $query
     */
    public static function query(array $query): string
    {
        ksort($query);

        return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Fail closed: no secret, no signature or a timestamp outside the window
     * is never valid. Constant-time comparison.
     */
    public static function verifyBody(string $secret, string $timestamp, string $signature, string $body, ?int $now = null): bool
    {
        if ($secret === '' || $signature === '' || ! ctype_digit($timestamp)) {
            return false;
        }

        $now ??= time();

        if (abs($now - (int) $timestamp) > self::WINDOW_SECONDS) {
            return false;
        }

        return hash_equals(self::forBody($secret, (int) $timestamp, $body), $signature);
    }

    private static function sign(string $secret, string $payload): string
    {
        return 'sha256='.hash_hmac('sha256', $payload, $secret);
    }
}
