<?php

namespace App\Modules\Commerce\Salla;

use App\Modules\Commerce\Data\TokenSet;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

/**
 * Salla's docs disagree on expiry: authorize sends `expires` (timestamp), refresh
 * sends `expires_in` (seconds). Accept both (salla-platform.md §OAuth).
 */
final class SallaTokens
{
    private const ACCESS_TOKEN_DAYS = 14;

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromResponse(array $payload): TokenSet
    {
        $access = $payload['access_token'] ?? null;
        $refresh = $payload['refresh_token'] ?? null;

        if (! is_string($access) || ! is_string($refresh) || $access === '' || $refresh === '') {
            throw new InvalidArgumentException('Salla token payload has no access or refresh token.');
        }

        return new TokenSet($access, $refresh, self::expiry($payload), is_string($payload['scope'] ?? null) ? $payload['scope'] : null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function expiry(array $payload): CarbonImmutable
    {
        $expiresIn = $payload['expires_in'] ?? null;
        $expires = $payload['expires'] ?? null;

        if (is_numeric($expiresIn)) {
            return CarbonImmutable::now()->addSeconds((int) $expiresIn);
        }

        if (is_numeric($expires)) {
            // A unix timestamp in the future, or (defensively) a duration in seconds.
            return (int) $expires > 1_000_000_000
                ? CarbonImmutable::createFromTimestamp((int) $expires)
                : CarbonImmutable::now()->addSeconds((int) $expires);
        }

        return CarbonImmutable::now()->addDays(self::ACCESS_TOKEN_DAYS);
    }
}
