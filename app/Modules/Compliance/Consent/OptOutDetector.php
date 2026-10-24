<?php

namespace App\Modules\Compliance\Consent;

use App\Shared\Arabic\ArabicText;

/**
 * Reads an inbound WhatsApp reply before anything else does (09 §9.8) and
 * decides whether it is an opt-out (S3, US-E04-02).
 *
 * A short message with a stop phrase is an opt-out. A stop phrase buried in a
 * longer message, or a "not now", only pauses the sequence and goes to a human.
 */
final class OptOutDetector
{
    /** Normalized with ArabicText::normalize (alef, taa marbuta and yaa unified). */
    private const OPT_OUT_PHRASES = [
        'stop', 'unsubscribe', 'opt out', 'ستوب',
        'الغاء', 'الغاء الاشتراك', 'الغي الاشتراك', 'ايقاف', 'ايقاف الرسايل', 'ايقاف الرسائل',
        'وقف', 'اوقف', 'وقفوا', 'اوقفوا', 'بطل', 'بطلوا',
        'لا ترسل', 'لا ترسلوا', 'لا ترسل لي', 'لا تراسلني', 'لا تراسلوني',
        'ما ابي رسايل', 'ما ابغي رسايل', 'مابي رسايل', 'مش عايز رسايل',
        'ايقاف العروض', 'لا اريد', 'لا ابغي',
    ];

    private const AMBIGUOUS_PHRASES = [
        'مش دلوقتي', 'مو الحين', 'مب الحين', 'بعدين', 'لاحقا', 'not now', 'later',
    ];

    private const SHORT_MESSAGE_WORDS = 6;

    public function detect(string $message): OptOutDecision
    {
        $text = ArabicText::normalize($message);

        if ($text === '') {
            return OptOutDecision::None;
        }

        $hasStopPhrase = $this->containsAny($text, self::OPT_OUT_PHRASES);

        if ($hasStopPhrase && count(explode(' ', $text)) <= self::SHORT_MESSAGE_WORDS) {
            return OptOutDecision::OptOut;
        }

        if ($hasStopPhrase || $this->containsAny($text, self::AMBIGUOUS_PHRASES)) {
            return OptOutDecision::Ambiguous;
        }

        return OptOutDecision::None;
    }

    /**
     * @param  list<string>  $phrases
     */
    private function containsAny(string $text, array $phrases): bool
    {
        foreach ($phrases as $phrase) {
            if (preg_match('/(^| )'.preg_quote($phrase, '/').'( |$)/u', $text)) {
                return true;
            }
        }

        return false;
    }
}
