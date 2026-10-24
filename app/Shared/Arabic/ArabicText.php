<?php

namespace App\Shared\Arabic;

/**
 * One Arabic normalization used everywhere we match text: opt-out keywords
 * now, catalog search later (E09), so both follow the same rules.
 */
final class ArabicText
{
    private const DIGITS = [
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
    ];

    private const LETTERS = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي',
    ];

    public static function digitsToLatin(string $text): string
    {
        return strtr($text, self::DIGITS);
    }

    /**
     * Lowercase, Latin digits, unified alef/taa marbuta/yaa, no tashkeel or
     * tatweel, punctuation and emoji turned into spaces, single spaces.
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(self::digitsToLatin($text));
        $text = strtr($text, self::LETTERS);
        $text = (string) preg_replace('/[\x{064B}-\x{0652}\x{0670}\x{0640}]/u', '', $text);
        $text = (string) preg_replace('/[^\p{Arabic}\p{Latin}\p{N}]+/u', ' ', $text);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }
}
