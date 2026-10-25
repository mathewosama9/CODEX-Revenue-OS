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

    /** Arabizi digits and digraphs, longest first. */
    private const ARABIZI = [
        'sh' => 'ش', 'ch' => 'ش', 'kh' => 'خ', 'gh' => 'غ', 'th' => 'ث', 'dh' => 'ذ',
        'oo' => 'و', 'ou' => 'و', 'ee' => 'ي', 'aa' => 'ا',
        '2' => 'ا', '3' => 'ع', '5' => 'خ', '6' => 'ط', '7' => 'ح', '8' => 'غ', '9' => 'ق',
        'b' => 'ب', 't' => 'ت', 'j' => 'ج', 'g' => 'ج', 'h' => 'ه', 'd' => 'د', 'r' => 'ر', 'z' => 'ز',
        's' => 'س', 'f' => 'ف', 'q' => 'ق', 'k' => 'ك', 'l' => 'ل', 'm' => 'م', 'n' => 'ن', 'w' => 'و',
        'a' => 'ا', 'e' => 'ي', 'y' => 'ي', 'i' => 'ي', 'o' => 'و', 'u' => 'و', 'v' => 'ف', 'p' => 'ب', 'c' => 'ك', 'x' => 'كس',
    ];

    /**
     * Basic Arabizi → Arabic letters, for search only (E09): "3ood" → "عود",
     * "shanel" → "شانل". A short "e" inside a word is dropped, as Arabic
     * spelling does. Approximate on purpose: trigram matching absorbs
     * the rest.
     */
    public static function fromArabizi(string $text): string
    {
        $words = preg_split('/\s+/u', mb_strtolower(trim($text))) ?: [];

        return implode(' ', array_map(function (string $word) {
            if (! preg_match('/[a-z]/', $word)) {
                return $word;
            }

            $first = $word[0];
            $rest = (string) preg_replace('/(?<!e)e(?!e)/', '', substr($word, 1));
            $word = (in_array($first, ['a', 'e'], true) ? 'ا' : $first).$rest;

            return strtr($word, self::ARABIZI);
        }, $words));
    }

    /**
     * The forms a search query is matched in: normalized, plus its Arabizi
     * reading when it has Latin letters.
     *
     * @return list<string>
     */
    public static function searchForms(string $text): array
    {
        $forms = [self::normalize($text)];

        if (preg_match('/[a-z]/i', $text)) {
            $forms[] = self::normalize(self::fromArabizi(self::digitsToLatin($text)));
        }

        return array_values(array_unique(array_filter($forms, fn (string $form) => $form !== '')));
    }

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
