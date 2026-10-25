<?php

namespace App\Modules\SalesAgent;

use App\Shared\Arabic\ArabicText;

/**
 * Handoff triggers decided by code before the LLM, never by the model's
 * judgement (E11 tasks, 09 §9.11.3). Words are matched after the shared
 * Arabic normalization, with the common prefixes (و، ب، ال، لل) peeled off.
 *
 * [افتراض] The word lists are a start; the golden set and real chats will
 * grow them. A false positive costs a human reply, a miss costs trust.
 */
final class HandoffRules
{
    private const HUMAN = [
        'موظف', 'موظفه', 'انسان', 'مندوب', 'ممثل', 'human', 'agent', 'representative', 'operator',
        'خدمه العملاء', 'شخص حقيقي', 'احد يكلمني', 'حد يكلمني', 'كلمني حد', 'كلمني احد', 'ابي اكلم', 'ابغي اكلم',
        'ابغا اكلم', 'عايز اكلم', 'اكلم حد', 'اكلم احد', 'اتصلوا علي', 'اتصل علي', 'real person', 'customer service',
        'talk to someone', 'speak to someone',
    ];

    private const COMPLAINT = [
        'شكوي', 'اشتكي', 'مشكله', 'مشاكل', 'متاخر', 'تاخر', 'تالف', 'مكسور', 'خربان', 'خربانه', 'نصب', 'احتيال',
        'زعلان', 'سيء', 'سيئ', 'اتاخر', 'اتاخرت', 'باظ', 'بايظ', 'complaint', 'problem', 'broken', 'damaged', 'scam', 'refund',
        'ما وصل', 'ماوصل', 'لم يصل', 'ما جاني', 'ماجاني', 'وصلني غلط', 'رجعوا فلوسي', 'ابي فلوسي', 'never arrived', 'not arrived',
    ];

    private const DISCOUNT = [
        'خصم', 'خصومات', 'كوبون', 'كوبونات', 'تخفيض', 'تخفيضات', 'عروض', 'ارخص', 'تفاوض', 'discount', 'coupon',
        'promo', 'cheaper', 'deal', 'كود خصم', 'اخر سعر', 'اقل سعر', 'تنزل السعر', 'نزل السعر', 'تنزل لي السعر', 'نزل لي السعر', 'تنزلي', 'اتفاوض', 'في عرض',
        'فيه عرض', 'عليه عرض', 'best price', 'last price', 'lower price',
    ];

    public function check(string $message, ?int $cartHalalas = null, ?int $cartThresholdHalalas = null): ?HandoffReason
    {
        $text = ArabicText::normalize(ArabicText::withoutArabicPunctuation($message));
        $tokens = self::tokens($text);

        return match (true) {
            self::matches($text, $tokens, self::HUMAN) => HandoffReason::CustomerAskedHuman,
            self::matches($text, $tokens, self::COMPLAINT) => HandoffReason::Complaint,
            self::matches($text, $tokens, self::DISCOUNT) => HandoffReason::DiscountRequest,
            $cartHalalas !== null && $cartThresholdHalalas !== null && $cartHalalas >= $cartThresholdHalalas => HandoffReason::HighCartValue,
            default => null,
        };
    }

    /**
     * Rule 9 on the way out: a reply that talks about a discount is never
     * sent, even one the model was tricked into (e.g. by text hidden in a
     * product description). A person answers instead.
     */
    public function mentionsDiscount(string $reply): bool
    {
        $text = ArabicText::normalize(ArabicText::withoutArabicPunctuation($reply));

        return self::matches($text, self::tokens($text), self::DISCOUNT);
    }

    /**
     * @return array<string, true>
     */
    private static function tokens(string $text): array
    {
        $tokens = [];

        foreach (explode(' ', $text) as $word) {
            $tokens[$word] = true;
            $bare = (string) preg_replace('/^(وال|بال|لل|ال|و|ب|ف)(?=\p{Arabic}{3,})/u', '', $word);
            $tokens[$bare] = true;
            // "والخصم" → "خصم": a second peel after "و" or "ب".
            $tokens[(string) preg_replace('/^ال(?=\p{Arabic}{3,})/u', '', $bare)] = true;
        }

        return $tokens;
    }

    /**
     * @param  array<string, true>  $tokens
     * @param  list<string>  $words
     */
    private static function matches(string $text, array $tokens, array $words): bool
    {
        foreach ($words as $word) {
            $word = ArabicText::normalize($word);

            if (str_contains($word, ' ') ? str_contains(' '.$text.' ', ' '.$word.' ') : isset($tokens[$word])) {
                return true;
            }
        }

        return false;
    }
}
