<?php

namespace App\Shared\Money;

use InvalidArgumentException;

/**
 * One currency of config('currencies.list') (ADR-012). Money is integer
 * minor units: amount × 10^exponent. Conversions go through strings, never
 * floats, so a price is exact to its last digit (3 for KWD, 0 for IQD).
 */
final readonly class Currency
{
    /**
     * @param  array{ar: string, en: string}  $symbol
     * @param  list<string>  $words
     */
    public function __construct(
        public string $code,
        public int $exponent,
        public array $symbol,
        public array $words,
        public float $usdRate,
    ) {}

    /**
     * "345", "12.5", "12.345", "-3.10" → minor units. Extra decimals round
     * half away from zero ("1.005" SAR → 101). Throws on anything else.
     */
    public function toMinor(string|int $decimal): int
    {
        $value = trim((string) $decimal);

        if (preg_match('/^([+-]?)(\d*)(?:\.(\d*))?$/', $value, $m) !== 1 || ($m[2] === '' && ($m[3] ?? '') === '')) {
            throw new InvalidArgumentException("Not a decimal amount: '{$value}'.");
        }

        $sign = $m[1] === '-' ? -1 : 1;
        $fraction = $m[3] ?? '';
        $kept = str_pad(substr($fraction, 0, $this->exponent), $this->exponent, '0');
        $digits = ltrim(($m[2] === '' ? '0' : $m[2]).$kept, '0');

        // 18 digits always fit in a bigint, rounding up included; more would silently saturate.
        if (strlen($digits) > 18) {
            throw new InvalidArgumentException("Amount too large: '{$value}'.");
        }

        $minor = (int) $digits;

        // Round on the first dropped digit.
        if ((int) substr($fraction, $this->exponent, 1) >= 5) {
            $minor++;
        }

        return $sign * $minor;
    }

    /** Minor units → "12.345" (exponent digits, no grouping); "345" for exponent 0. */
    public function toMajor(int $minor): string
    {
        $sign = $minor < 0 ? '-' : '';
        $digits = str_pad((string) abs($minor), $this->exponent + 1, '0', STR_PAD_LEFT);

        if ($this->exponent === 0) {
            return $sign.$digits;
        }

        return $sign.substr($digits, 0, -$this->exponent).'.'.substr($digits, -$this->exponent);
    }

    /** The cost ledger keeps micros of the major unit: 1 minor unit = this many micros. */
    public function microsPerMinor(): int
    {
        return 10 ** (6 - $this->exponent);
    }

    public function symbol(string $locale): string
    {
        return $this->symbol[$locale === 'en' ? 'en' : 'ar'];
    }

    /** USD amount (e.g. a Meta rate) → micros of this currency. */
    public function microsFromUsd(float $usd): int
    {
        return (int) round($usd * $this->usdRate * 1_000_000);
    }

    /** Minor units of this currency worth about $usd (thresholds set in dollars). */
    public function minorFromUsd(float $usd): int
    {
        return (int) round($usd * $this->usdRate * 10 ** $this->exponent);
    }

    /**
     * @return array{code: string, exponent: int, symbol: string}
     */
    public function toArray(string $locale = 'ar'): array
    {
        return ['code' => $this->code, 'exponent' => $this->exponent, 'symbol' => $this->symbol($locale)];
    }
}
