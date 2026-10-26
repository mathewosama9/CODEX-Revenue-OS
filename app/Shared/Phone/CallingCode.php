<?php

namespace App\Shared\Phone;

/**
 * ISO 3166 country of an E.164 number, from its calling code. Only the Arab
 * countries the product sells to (ADR-012); anything else is unknown, and a
 * cost priced by country then falls back to an estimate.
 */
final class CallingCode
{
    /** Calling code => ISO country. ITU-T E.164 assignments. */
    private const COUNTRIES = [
        '966' => 'SA', '971' => 'AE', '974' => 'QA', '965' => 'KW', '973' => 'BH', '968' => 'OM',
        '962' => 'JO', '964' => 'IQ', '961' => 'LB', '963' => 'SY', '970' => 'PS',
        '20' => 'EG', '249' => 'SD', '218' => 'LY', '216' => 'TN', '213' => 'DZ', '212' => 'MA', '222' => 'MR',
        '967' => 'YE', '252' => 'SO', '253' => 'DJ', '269' => 'KM',
    ];

    /**
     * National mobile numbers (without the trunk 0), for reading a local
     * number typed in a known country. Same table as the WordPress plugin.
     */
    private const MOBILES = [
        'SA' => ['966', '5\d{8}'], 'EG' => ['20', '1[0125]\d{8}'], 'AE' => ['971', '5\d{8}'],
        'KW' => ['965', '[569]\d{7}'], 'QA' => ['974', '[3567]\d{7}'], 'BH' => ['973', '[36]\d{7}'],
        'OM' => ['968', '[79]\d{7}'], 'JO' => ['962', '7[789]\d{7}'], 'IQ' => ['964', '7\d{9}'],
        'LB' => ['961', '(?:3\d{6}|7[01689]\d{6}|81\d{6})'], 'SY' => ['963', '9\d{8}'], 'PS' => ['970', '5[69]\d{7}'],
        'YE' => ['967', '7\d{8}'], 'MA' => ['212', '[67]\d{8}'], 'DZ' => ['213', '[567]\d{8}'],
        'TN' => ['216', '[2-9]\d{7}'], 'LY' => ['218', '9[1-6]\d{7}'], 'SD' => ['249', '[19]\d{8}'], 'MR' => ['222', '[234]\d{7}'],
    ];

    /** Whether local numbers typed in $country can be read (an Arab country we know). */
    public static function readsLocal(string $country): bool
    {
        return isset(self::MOBILES[strtoupper($country)]);
    }

    /**
     * A local number typed in $country ("010…" in EG, "05…" in SA) → E.164
     * digits without "+", or null when it is not a mobile there.
     */
    public static function localToInternational(string $digits, string $country): ?string
    {
        $rule = self::MOBILES[strtoupper($country)] ?? null;

        if ($rule === null) {
            return null;
        }

        [$code, $mobile] = $rule;
        $national = ltrim($digits, '0');

        // Typed with the country code but without "+" or "00" (9665…, 2010…).
        if (str_starts_with($digits, $code) && preg_match('/^'.$mobile.'$/', substr($digits, strlen($code))) === 1) {
            $national = substr($digits, strlen($code));
        }

        return preg_match('/^'.$mobile.'$/', $national) === 1 ? $code.$national : null;
    }

    public static function country(PhoneNumber|string|null $phone): ?string
    {
        $digits = ltrim((string) $phone, '+');

        // Calling codes are prefix-free, so the first match is the only one.
        foreach ([3, 2, 1] as $length) {
            if (isset(self::COUNTRIES[$prefix = substr($digits, 0, $length)])) {
                return self::COUNTRIES[$prefix];
            }
        }

        return null;
    }
}
