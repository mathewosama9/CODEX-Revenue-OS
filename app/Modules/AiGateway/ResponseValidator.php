<?php

namespace App\Modules\AiGateway;

use App\Modules\AiGateway\Data\ToolOutcome;
use App\Shared\Arabic\ArabicText;
use App\Shared\Money\Currencies;

/**
 * Deterministic guard on every AI reply (US-E10-03, 06 B.5.3): each number,
 * price, quantity or percentage must equal a value a tool returned in the
 * same turn. No model judgement: a reply that fails is blocked.
 *
 * Two sets (review: correctness #1): a number next to a currency or a percent
 * sign must be money from a price field (`price`, `was_price`, `*_price`, and
 * the legacy `*_sar`, `*_halalas` also /100) or from the merchant's policy
 * text (`text`); any other number may also come from a quantity, a name
 * ("100ml") or a description. Ids and links never count. Every link must be a
 * tool's `url`. Numbers written as words next to a currency or a percent sign
 * are blocked, since they cannot be checked.
 *
 * Currency (ADR-012): the words and symbols of EVERY supported currency mark
 * money, so «ريال» in a store that sells in pounds is checked like any price.
 * Numbers are compared as exact decimals: "345" = "345.00", "12.345" ≠ "12.35".
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

    /** Percent words; currency words come from config/currencies.php. */
    /** Words that make a percentage a discount, after ArabicText::normalize. */
    private const DISCOUNT_NEAR_PERCENT = '/(خصم|خصومات|تخفيض|تنزيل|عرض|اوفر|توفير|كوبون|off|discount|sale|save|coupon|promo)/u';

    /** @var list<string> percentages stated in tool text, outside a discount */
    private array $percents = [];

    private const PERCENT_WORDS = ['percent', 'بالميه', 'بالمئه', 'بالمائه', 'في المئه'];

    /** Arabic words that also start currency words but are not money ("ريال" stays, "جنيهك" is fine). */
    private const MIN_PREFIX_CHARS = 4;

    /** @var array{words: list<string>, before: list<string>}|null */
    private static ?array $vocabulary = null;

    /**
     * @param  list<ToolOutcome>  $tools
     * @param  list<string>  $trusted  text we wrote ourselves, e.g. the store name
     */
    public function validate(string $reply, array $tools, array $trusted = []): ValidationResult
    {
        $money = [];
        $plain = [];
        $this->percents = [];
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

        $currencyWords = self::currencyWords($tools);
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
            $unit = self::moneyContext(mb_strcut($text, max(0, $offset - 16), $offset - max(0, $offset - 16)), mb_strcut($text, $offset + strlen($number), 32));
            $allowed = match ($unit) {
                null => [...$money, ...$plain],
                // "قطن 100%" copied from a description is a fact, not a price.
                '%' => [...$money, ...$this->percents],
                default => $money,
            };

            if (! in_array(self::canonical($number), $allowed, true)) {
                $unmatched[] = $number;
            }

            // The right number in the wrong currency ("345 ريال" in a pound store) misleads too.
            if ($unit !== null && $unit !== '%' && $currencyWords !== [] && ! self::isMoneyWord($unit, $currencyWords)) {
                $unmatched[] = 'currency:'.$unit;
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
     * @param  list<string>  $money  canonical decimals
     * @param  list<string>  $plain  canonical decimals
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
            } elseif (str_ends_with($key, '_halalas') && is_int($value)) {
                // Legacy minor units of a 2-decimal currency: the number itself or /100.
                $money[] = (string) $value;
                $money[] = self::canonical(self::decimal(intdiv(abs($value), 100)).'.'.str_pad((string) (abs($value) % 100), 2, '0', STR_PAD_LEFT), $value < 0);
            } elseif (self::isPriceKey($key) && (is_int($value) || is_float($value) || (is_string($value) && preg_match('/^-?\d+(?:\.\d+)?$/', $value) === 1))) {
                // An exact major-unit string from GetProduct ("12.345"), or a legacy number.
                $money[] = self::canonical(is_string($value) ? $value : self::decimal($value));
            } elseif (is_int($value) || is_float($value)) {
                $plain[] = self::canonical(self::decimal($value));
            } elseif (is_string($value)) {
                // The merchant's own policy text may state money ("فوق 300 ريال"); a name or a description may not.
                if ($policy && $key === 'text') {
                    self::numbersIn($value, $money);
                }

                $this->percentsIn($value);

                self::numbersIn($value, $plain);
            }
        }
    }

    /**
     * Percentages a tool's text states ("قطن 100%"), except next to a discount
     * word: «خصم 50%» planted in a description must never reach a customer
     * (Rule 9; the discount check hands such replies off as well).
     */
    private function percentsIn(string $text): void
    {
        $text = self::normalizeNumbers(str_replace('٪', '%', $text));
        preg_match_all('/(\d+(?:\.\d+)?)\s*%/u', $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as [$number, $offset]) {
            $before = ArabicText::normalize(mb_strcut($text, max(0, $offset - 40), $offset - max(0, $offset - 40)));

            if (preg_match(self::DISCOUNT_NEAR_PERCENT, $before) !== 1) {
                $this->percents[] = self::canonical($number);
            }
        }
    }

    /** `price`, `was_price`, `sale_price`, and the legacy `*_sar`. */
    private static function isPriceKey(string $key): bool
    {
        return $key === 'price' || str_ends_with($key, '_price') || str_ends_with($key, '_sar');
    }

    /**
     * @param  list<string>  $into
     */
    private static function numbersIn(string $text, array &$into): void
    {
        preg_match_all('/\d+(?:\.\d+)?/', self::normalizeNumbers($text), $matches);

        foreach ($matches[0] as $number) {
            $into[] = self::canonical($number);
        }
    }

    /** A tool number as a plain decimal string, never in exponent notation. */
    private static function decimal(int|float $value): string
    {
        return is_int($value) ? (string) $value : rtrim(rtrim(sprintf('%.6F', $value), '0'), '.');
    }

    /** "0345.00" → "345", "12.340" → "12.34": the same amount, the same string. */
    private static function canonical(string $number, bool $negative = false): string
    {
        $number = ltrim($number, '+');

        if (str_starts_with($number, '-')) {
            $negative = true;
            $number = substr($number, 1);
        }

        [$whole, $fraction] = array_pad(explode('.', $number, 2), 2, '');
        $whole = ltrim($whole, '0') ?: '0';
        $fraction = rtrim($fraction, '0');
        $value = $fraction === '' ? $whole : $whole.'.'.$fraction;

        return $negative && $value !== '0' ? '-'.$value : $value;
    }

    /**
     * "199 ريال", "345 ج.م", "12.345 د.ك", "SAR 199", "$25", "20%": the number
     * states money, in any supported currency. Returns the unit it found
     * ("ريال", "ج.م", "sar", "$", "%"), or null for a plain number.
     */
    private static function moneyContext(string $before, string $after): ?string
    {
        $after = ltrim($after);

        if (str_starts_with($after, '%')) {
            return '%';
        }

        if (str_starts_with($after, '$') || str_ends_with(rtrim($before), '$')) {
            return '$';
        }

        $vocabulary = self::vocabulary();
        $next = self::tokens($after)[0] ?? '';

        if (self::isMoneyWord($next, self::PERCENT_WORDS)) {
            return '%';
        }

        if (self::isMoneyWord($next, $vocabulary['words'])) {
            return $next;
        }

        $previous = self::tokens($before);
        $previous = end($previous) ?: '';

        // Before the number only codes and symbols ("SAR 199", "ج.م 345"): a word
        // before it usually ends the previous price ("199 ريال و 3 حبات").
        return in_array($previous, $vocabulary['before'], true) ? $previous : null;
    }

    /**
     * The words of the currency the tools priced in (`currency`, e.g. GetProduct),
     * or [] when no tool said: then any currency word only marks money.
     *
     * @param  list<ToolOutcome>  $tools
     * @return list<string>
     */
    private static function currencyWords(array $tools): array
    {
        $words = [];

        foreach ($tools as $tool) {
            $code = $tool->failed ? null : ($tool->result['currency'] ?? null);

            if (is_string($code) && Currencies::supports($code)) {
                $currency = Currencies::get($code);

                foreach ([...$currency->words, $currency->symbol['ar'], $currency->symbol['en'], strtolower($currency->code)] as $word) {
                    $words = [...$words, ...self::tokens($word)];
                }

                if (str_contains($currency->symbol['en'], '$')) {
                    $words[] = '$';
                }
            }
        }

        return array_values(array_unique($words));
    }

    /**
     * @param  list<string>  $words
     */
    private static function isMoneyWord(string $token, array $words): bool
    {
        if ($token === '') {
            return false;
        }

        foreach ($words as $word) {
            // Arabic words also match their forms: «جنيهات», «ريالين», «دينارًا».
            if ($token === $word || (mb_strlen($word) >= self::MIN_PREFIX_CHARS && preg_match('/^\p{Arabic}+$/u', $word) === 1 && str_starts_with($token, $word))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalized words (ArabicText::normalize), except that a dotted Arabic
     * symbol stays one token: «ج.م» → "ج.م", «ر. س» → "ر.س", so the Egyptian
     * «دا» is not read as «د.ا».
     *
     * @return list<string>
     */
    private static function tokens(string $text): array
    {
        $text = (string) preg_replace('/(?<!\p{Arabic})(\p{Arabic})\s?\.\s?(\p{Arabic})(?!\p{Arabic})/u', '$1zzdotzz$2', $text);
        $words = explode(' ', ArabicText::normalize($text));

        return array_values(array_filter(array_map(fn (string $word) => str_replace('zzdotzz', '.', $word), $words), fn (string $word) => $word !== ''));
    }

    /**
     * @return array{words: list<string>, before: list<string>}
     */
    private static function vocabulary(): array
    {
        if (self::$vocabulary !== null) {
            return self::$vocabulary;
        }

        $words = [];
        $before = [];

        foreach (Currencies::codes() as $code) {
            $currency = Currencies::get($code);

            foreach ([...$currency->words, $currency->symbol['ar'], $currency->symbol['en']] as $word) {
                $words = [...$words, ...self::tokens($word)];
            }

            foreach ([strtolower($code), $currency->symbol['ar'], $currency->symbol['en']] as $symbol) {
                $before = [...$before, ...self::tokens($symbol)];
            }

            // Arabic symbols, dotted or not: «ر.س» and «رس».
            foreach ($currency->words as $word) {
                if (preg_match('/^\p{Arabic}\.?\p{Arabic}$/u', $word) === 1 || preg_match('/^[a-z]{2,3}$/', $word) === 1) {
                    $before = [...$before, ...self::tokens($word)];
                }
            }
        }

        return self::$vocabulary = [
            'words' => array_values(array_unique($words)),
            'before' => array_values(array_unique($before)),
        ];
    }

    private static function hasNumberInWords(string $text): bool
    {
        $text = str_replace('%', ' percent ', $text);
        $words = self::tokens($text);
        $words = array_map(fn (string $word) => (string) preg_replace('/^(و|ب|ال|بال|وال)(?=\p{Arabic}{3,})/u', '', $word), $words);
        $money = [...self::vocabulary()['words'], ...self::PERCENT_WORDS];

        foreach ($words as $i => $word) {
            if (! in_array($word, self::NUMBER_WORDS, true)) {
                continue;
            }

            foreach (array_slice($words, $i + 1, 3) as $next) {
                if (self::isMoneyWord($next, $money)) {
                    return true;
                }
            }
        }

        return false;
    }
}
