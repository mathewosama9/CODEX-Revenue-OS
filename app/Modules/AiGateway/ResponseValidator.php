<?php

namespace App\Modules\AiGateway;

use App\Modules\AiGateway\Data\ToolOutcome;
use App\Shared\Arabic\ArabicText;

/**
 * Deterministic guard on every AI reply (US-E10-03, 06 B.5.3): each number,
 * price, quantity or percentage must equal a value a tool returned in the
 * same turn. No model judgement: a reply that fails is blocked.
 *
 * Money from tools is in halalas (`*_halalas`), so both 19900 and 199 are
 * accepted for it. Numbers written as words next to a currency or a percent
 * sign are blocked too, since they cannot be checked.
 */
final class ResponseValidator
{
    private const NUMBER_WORDS = [
        'واحد', 'اثنين', 'اتنين', 'ثلاث', 'ثلاثه', 'تلاته', 'اربع', 'اربعه', 'خمس', 'خمسه', 'ست', 'سته', 'سبع', 'سبعه',
        'ثمان', 'ثمانيه', 'تمانيه', 'تسع', 'تسعه', 'عشر', 'عشره', 'عشرين', 'ثلاثين', 'اربعين', 'خمسين', 'ستين', 'سبعين',
        'ثمانين', 'تسعين', 'ميه', 'مايه', 'مائه', 'ميتين', 'مئتين', 'الف', 'الفين', 'نص', 'نصف', 'ربع',
        'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'twenty', 'thirty', 'forty',
        'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred', 'thousand', 'half',
    ];

    private const MONEY_WORDS = ['ريال', 'ريالات', 'رس', 'sar', 'sr', 'riyal', 'riyals', 'percent', 'بالميه', 'بالمئه'];

    /**
     * @param  list<ToolOutcome>  $tools
     */
    public function validate(string $reply, array $tools): ValidationResult
    {
        $allowed = [];

        foreach ($tools as $tool) {
            if (! $tool->failed) {
                $this->collect($tool->result, $allowed);
            }
        }

        $text = self::normalizeNumbers($reply);
        // Pseudonym tokens such as [PHONE_A] are not numbers the customer reads as facts.
        $text = (string) preg_replace('/\[[A-Z]+_[A-Z]+\]/', ' ', $text);

        $unmatched = [];

        preg_match_all('/\d+(?:\.\d+)?/', $text, $matches);

        foreach ($matches[0] as $number) {
            if (! self::contains($allowed, (float) $number)) {
                $unmatched[] = $number;
            }
        }

        if (self::hasNumberInWords($text)) {
            $unmatched[] = 'number_in_words';
        }

        return new ValidationResult(array_values(array_unique($unmatched)));
    }

    /**
     * Latin digits, "." decimals, no thousands separators: ١٬٢٩٩٫٥٠ → 1299.50.
     */
    public static function normalizeNumbers(string $text): string
    {
        $text = ArabicText::digitsToLatin($text);
        $text = str_replace(['٫', '٪'], ['.', '%'], $text);
        $text = (string) preg_replace('/(?<=\d)[,٬](?=\d{3}\b)/u', '', $text);

        return $text;
    }

    /**
     * @param  array<array-key, mixed>  $values
     * @param  list<float>  $allowed
     */
    private function collect(array $values, array &$allowed): void
    {
        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $this->collect($value, $allowed);
            } elseif (is_int($value) || is_float($value)) {
                $allowed[] = (float) $value;

                if (is_string($key) && str_ends_with($key, '_halalas')) {
                    $allowed[] = (float) $value / 100;
                }
            } elseif (is_string($value)) {
                preg_match_all('/\d+(?:\.\d+)?/', self::normalizeNumbers($value), $matches);

                foreach ($matches[0] as $number) {
                    $allowed[] = (float) $number;
                }
            }
        }
    }

    /**
     * @param  list<float>  $allowed
     */
    private static function contains(array $allowed, float $number): bool
    {
        foreach ($allowed as $value) {
            if (abs($value - $number) < 0.001) {
                return true;
            }
        }

        return false;
    }

    private static function hasNumberInWords(string $text): bool
    {
        $text = str_replace(['%', 'ر.س', 'ر. س'], [' percent ', 'رس', 'رس'], $text);
        $words = explode(' ', ArabicText::normalize($text));
        $words = array_map(fn (string $word) => (string) preg_replace('/^(و|ب|ال|بال|وال)(?=\p{Arabic}{3,})/u', '', $word), $words);

        foreach ($words as $i => $word) {
            if (in_array($word, self::NUMBER_WORDS, true)
                && array_intersect(array_slice($words, $i + 1, 3), self::MONEY_WORDS) !== []) {
                return true;
            }
        }

        return false;
    }
}
