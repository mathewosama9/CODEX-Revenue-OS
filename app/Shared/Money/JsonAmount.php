<?php

namespace App\Shared\Money;

/**
 * An amount as a platform sends it ("199.00", 249.5, 12) → an exact decimal
 * string for Currency::toMinor (ADR-012), never through float × 100.
 */
final class JsonAmount
{
    /** Null for anything that is not a number ("", null, "abc", INF). */
    public static function decimal(mixed $amount): ?string
    {
        if (is_int($amount)) {
            return (string) $amount;
        }

        if (is_float($amount)) {
            if (! is_finite($amount)) {
                return null;
            }

            // The shortest form that reads back the same: 12.345 stays "12.345".
            $amount = (string) $amount;
        }

        if (! is_string($amount) || ! is_numeric($amount)) {
            return null;
        }

        $amount = trim($amount);

        // "1e3" and "1.5E-2" are numeric but not plain decimals.
        return preg_match('/^[+-]?(\d+(\.\d*)?|\.\d+)$/', $amount) === 1 ? $amount : sprintf('%.6F', (float) $amount);
    }
}
