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
        'زعلان', 'سيء', 'سيئ', 'اتاخر', 'اتاخرت', 'باظ', 'بايظ', 'complaint', 'broken', 'damaged', 'scam',
        'ما وصل', 'ماوصل', 'لم يصل', 'ما وصلني', 'ماوصلني', 'ما وصلتني', 'لسه ما وصل', 'ما جاني', 'ماجاني', 'وصلني غلط',
        'رجعوا فلوسي', 'ابي فلوسي', 'never arrived', 'not arrived', 'i want a refund', 'refund my',
    ];

    /**
     * What a customer says to bargain. Asking for "the cheapest perfume" is a
     * sales question, not a discount request, so bare ارخص/cheaper stay out.
     */
    private const DISCOUNT = [
        'خصم', 'خصومات', 'كوبون', 'كوبونات', 'تخفيض', 'تخفيضات', 'عروض', 'تفاوض', 'اتفاوض', 'discount', 'coupon',
        'promo', 'voucher', 'كود خصم', 'اخر سعر', 'اقل سعر', 'ارخص لي', 'تنزل السعر', 'نزل السعر', 'تنزل لي السعر',
        'نزل لي السعر', 'تنزلي', 'في عرض', 'فيه عرض', 'عليه عرض', 'عرض خاص', 'سعر خاص', 'برومو',
        'best price', 'last price', 'lower price', 'special price', 'special offer', 'promo code',
    ];

    /** What the agent must never write (Rule 9). Narrower: "عندنا عروض" or "الأرخص" are normal sales talk. */
    private const DISCOUNT_OUT = [
        'خصم', 'خصومات', 'كوبون', 'كوبونات', 'تخفيض', 'تخفيضات', 'كود خصم', 'سعر خاص', 'عرض خاص', 'برومو',
        'discount', 'coupon', 'voucher', 'promo code', 'special price', 'special offer',
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

        return self::matches($text, self::tokens($text), self::DISCOUNT_OUT);
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

    /** "وما وصل لي" → "ما وصل لي": a glued "و" before a negation, so phrases still match. */
    private static function peeled(string $text): string
    {
        return (string) preg_replace('/(^| )و(ما|لا|لم|لسه|لسا)(?= )/u', '$1$2', $text);
    }

    /**
     * @param  array<string, true>  $tokens
     * @param  list<string>  $words
     */
    private static function matches(string $text, array $tokens, array $words): bool
    {
        foreach ($words as $word) {
            $word = ArabicText::normalize($word);

            if (str_contains($word, ' ')
                ? str_contains(' '.$text.' ', ' '.$word.' ') || str_contains(' '.self::peeled($text).' ', ' '.$word.' ')
                : isset($tokens[$word])) {
                return true;
            }
        }

        return false;
    }
}
