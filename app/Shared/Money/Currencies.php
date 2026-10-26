<?php

namespace App\Shared\Money;

use InvalidArgumentException;

/** The supported currencies (config/currencies.php, ADR-012). */
final class Currencies
{
    public static function get(string $code): Currency
    {
        $code = strtoupper(trim($code));
        $row = config("currencies.list.{$code}");

        if (! is_array($row)) {
            throw new InvalidArgumentException("Unsupported currency '{$code}'.");
        }

        return new Currency(
            $code,
            (int) $row['exponent'],
            ['ar' => (string) $row['symbol']['ar'], 'en' => (string) $row['symbol']['en']],
            array_values(array_map('strval', (array) $row['words'])),
            (float) $row['usd_rate'],
        );
    }

    public static function supports(?string $code): bool
    {
        return is_string($code) && is_array(config('currencies.list.'.strtoupper(trim($code))));
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys((array) config('currencies.list'));
    }

    /**
     * Every currency's words and symbols: a number next to any of them is a
     * price, whatever the store's currency (the response validator).
     *
     * @return list<string>
     */
    public static function allMoneyWords(): array
    {
        $words = [];

        foreach (self::codes() as $code) {
            $currency = self::get($code);
            $words = [...$words, ...$currency->words, mb_strtolower($currency->symbol['ar']), mb_strtolower($currency->symbol['en'])];
        }

        return array_values(array_unique($words));
    }
}
