<?php

namespace App\Shared\Phone;

use App\Shared\Arabic\ArabicText;
use Stringable;

/**
 * A phone number normalized to E.164. Local formats are read in the given
 * country (the order's billing country, ADR-012) or, by default, Saudi
 * (05…, 5…, 9665… → +9665…); anything else must carry a country code.
 */
final class PhoneNumber implements Stringable
{
    private function __construct(public readonly string $e164) {}

    public static function tryParse(?string $raw, ?string $country = null): ?self
    {
        if ($raw === null) {
            return null;
        }

        $raw = trim(ArabicText::digitsToLatin($raw));
        $hasPlus = str_starts_with($raw, '+');
        $digits = (string) preg_replace('/\D+/', '', $raw);

        if (! $hasPlus && str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            $hasPlus = true;
        }

        // An unknown or non-Arab country ("US", "") keeps the Saudi reading, as before ADR-012.
        if (! $hasPlus && $country !== null && strtoupper($country) !== 'SA' && CallingCode::readsLocal($country)) {
            $digits = CallingCode::localToInternational($digits, $country) ?? '';
        } elseif (! $hasPlus) {
            $digits = match (true) {
                (bool) preg_match('/^05\d{8}$/', $digits) => '966'.substr($digits, 1),
                (bool) preg_match('/^5\d{8}$/', $digits) => '966'.$digits,
                (bool) preg_match('/^9665\d{8}$/', $digits) => $digits,
                default => '',
            };
        }

        if (! preg_match('/^[1-9]\d{7,14}$/', $digits)) {
            return null;
        }

        // A Saudi number must be a valid mobile: +9665 and 8 more digits.
        if (str_starts_with($digits, '966') && ! preg_match('/^9665\d{8}$/', $digits)) {
            return null;
        }

        return new self('+'.$digits);
    }

    public function __toString(): string
    {
        return $this->e164;
    }
}
