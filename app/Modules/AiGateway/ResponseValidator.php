<?php

namespace App\Modules\AiGateway;

use App\Modules\AiGateway\Data\ToolOutcome;
use App\Shared\Arabic\ArabicText;

/**
 * Deterministic guard on every AI reply (US-E10-03, 06 B.5.3): each number,
 * price, quantity or percentage must equal a value a tool returned in the
 * same turn. No model judgement: a reply that fails is blocked.
 *
 * Two sets (review: correctness #1): a number next to a currency or a percent
 * sign must be money from a price field (`*_sar`, `*_halalas`, also /100) or
 * from the merchant's policy text (`text`); any other number may also come
 * from a quantity, a name ("100ml") or a description. Ids and links never
 * count. Every link must be a tool's `url`. Numbers written as words next to
 * a currency or a percent sign are blocked, since they cannot be checked.
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

    /** A scheme, `www.`, or a domain with a known suffix, plus its path. */
    private const LINK = '#(?:https?://|www\\.)[^\\s<>"\\)\\]]+|\\b[a-z0-9][a-z0-9\\-]*(?:\\.[a-z0-9\\-]+)*\\.(?:com|net|org|sa|me|ly|io|co|store|shop|link|app|test|info|xyz)\\b(?:/[^\\s<>"\\)\\]]*)?#iu';

    private const MONEY_WORDS = ['ريال', 'ريالات', 'ريالا', 'رس', 'sar', 'sr', 'riyal', 'riyals', 'percent', 'بالميه', 'بالمئه'];

    /**
     * @param  list<ToolOutcome>  $tools
     * @param  list<string>  $trusted  text we wrote ourselves, e.g. the store name
     */
    public function validate(string $reply, array $tools, array $trusted = []): ValidationResult
    {
        $money = [];
        $plain = [];
        $urls = [];

        foreach ($trusted as $text) {
            self::numbersIn($text, $plain);
        }

        foreach ($tools as $tool) {
            if (! $tool->failed) {
                $this->collect($tool->result, $money, $plain);
                $result = $tool->result;
                // Only a tool's `url` field, never a link inside free text (a description, a policy).
                array_walk_recursive($result, function ($value, $key) use (&$urls) {
                    if ($key === 'url' && is_string($value)) {
                        $urls[] = self::bareLink($value);
                    }
                });
            }
        }

        $unmatched = [];

        // Links, with or without a scheme (evil.test/pay, wa.me/…, bit.ly/…): only ones a tool returned.
        preg_match_all(self::LINK, $reply, $links);

        foreach ($links[0] as $link) {
            if (! in_array(self::bareLink($link), $urls, true)) {
                $unmatched[] = 'url:'.$link;
            }

            $reply = str_replace($link, ' ', $reply);
        }

        $text = self::normalizeNumbers($reply);
        // Pseudonym tokens such as [PHONE_A] are not numbers the customer reads as facts.
        $text = (string) preg_replace('/\[[A-Z]+_[A-Z]+\]/', ' ', $text);

        preg_match_all('/\d+(?:\.\d+)?/', $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$number, $offset]) {
            $isMoney = self::moneyContext(substr($text, max(0, $offset - 8), 8), substr($text, $offset + strlen($number), 16));
            $allowed = $isMoney ? $money : [...$money, ...$plain];

            if (! self::contains($allowed, (float) $number)) {
                $unmatched[] = $number;
            }
        }

        if (self::hasNumberInWords($text)) {
            $unmatched[] = 'number_in_words';
        }

        return new ValidationResult(array_values(array_unique($unmatched)));
    }

    /** Compare links without scheme, `www.` or trailing punctuation. */
    private static function bareLink(string $link): string
    {
        $link = mb_strtolower(rtrim($link, '.,،!؟'));

        return (string) preg_replace('#^(https?://)?(www\\.)?#', '', $link);
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
     * @param  list<float>  $money
     * @param  list<float>  $plain
     */
    private function collect(array $values, array &$money, array &$plain, bool $policy = false): void
    {
        foreach ($values as $key => $value) {
            $key = is_string($key) ? $key : '';

            // Ids and links are not facts for the customer: "1500 ريال" from product_id 1500 must fail.
            if ($key === 'id' || $key === 'url' || str_ends_with($key, '_id')) {
                continue;
            }

            if (is_array($value)) {
                $this->collect($value, $money, $plain, $policy || $key === 'passages');
            } elseif (is_int($value) || is_float($value)) {
                if (str_ends_with($key, '_halalas')) {
                    $money[] = (float) $value;
                    $money[] = (float) $value / 100;
                } elseif (str_ends_with($key, '_sar')) {
                    $money[] = (float) $value;
                } else {
                    $plain[] = (float) $value;
                }
            } elseif (is_string($value)) {
                // The merchant's own policy text may state money ("فوق 300 ريال"); a name or a description may not.
                if ($policy && $key === 'text') {
                    self::numbersIn($value, $money);
                }

                self::numbersIn($value, $plain);
            }
        }
    }

    /**
     * @param  list<float>  $into
     */
    private static function numbersIn(string $text, array &$into): void
    {
        preg_match_all('/\d+(?:\.\d+)?/', self::normalizeNumbers($text), $matches);

        foreach ($matches[0] as $number) {
            $into[] = (float) $number;
        }
    }

    /** "199 ريال", "SAR 199", "20%": the number states money. */
    private static function moneyContext(string $before, string $after): bool
    {
        $after = mb_strtolower(ltrim($after));
        $before = mb_strtolower(rtrim($before));

        return str_starts_with($after, '%')
            || (bool) preg_match('/^(?:ريال|ر\.?\s?س|رس|sar\b|sr\b|riyal|ريالا|بالمي|بالمئ|percent)/u', $after)
            || (bool) preg_match('/(?:sar|ر\.س)$/u', $before);
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
