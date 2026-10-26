<?php

namespace App\Modules\Commerce\WooCommerce;

/**
 * SSRF guard for merchant-supplied site addresses: a WooCommerce site must
 * resolve to public addresses only. WooClient pins the request to the
 * address checked here (CURLOPT_RESOLVE), so a DNS answer that changes
 * between the check and the request (rebinding) cannot reach inside.
 * Tests bind a fake (tests/TestCase.php): no test does a real DNS lookup.
 */
class WooHostResolver
{
    /**
     * IPv4 ranges PHP's FILTER_FLAG_NO_RES_RANGE lets through but that are
     * not the public internet: CGNAT, IETF protocol assignments, benchmarking.
     */
    private const EXTRA_BLOCKED_V4 = ['100.64.0.0/10', '192.0.0.0/24', '198.18.0.0/15'];

    /**
     * The public IPv4 addresses of `$host`, or an empty list when it does not
     * resolve or any of its addresses is private or reserved (fail closed).
     *
     * @return list<string>
     */
    public function publicAddresses(string $host): array
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::isPublicIp($host) ? [$host] : [];
        }

        $addresses = gethostbynamel($host);

        if ($addresses === false || $addresses === []) {
            return [];
        }

        foreach ($addresses as $address) {
            if (! self::isPublicIp($address)) {
                return [];
            }
        }

        return $addresses;
    }

    public static function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return true;
        }

        $long = ip2long($ip);

        foreach (self::EXTRA_BLOCKED_V4 as $cidr) {
            [$network, $bits] = explode('/', $cidr);
            $mask = -1 << (32 - (int) $bits);

            if (($long & $mask) === (ip2long($network) & $mask)) {
                return false;
            }
        }

        return true;
    }
}
