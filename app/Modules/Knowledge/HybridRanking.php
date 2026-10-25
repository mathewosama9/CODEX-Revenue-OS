<?php

namespace App\Modules\Knowledge;

use App\Shared\Arabic\ArabicText;

/**
 * Shared pieces of the hybrid search: query words and reciprocal rank
 * fusion of the text, trigram and vector lists (09 §9.12).
 */
final class HybridRanking
{
    /** Saudi/Egyptian filler words that never name a product (normalized). */
    private const FILLER = [
        'عندكم', 'عندك', 'فيه', 'في', 'ابي', 'ابغي', 'ابغا', 'ابغى', 'ودي', 'بكم', 'كم', 'سعر', 'السعر', 'ممكن', 'لو',
        'سمحت', 'هل', 'عن', 'من', 'على', 'يا', 'شي', 'وش', 'ايش', 'اريد', 'عايز', 'عاوز', 'محتاج', 'متوفر', 'موجود',
        'الله', 'يعطيك', 'العافيه', 'السلام', 'عليكم', 'مرحبا', 'هلا', 'اهلا', 'بغيت', 'حق', 'ال', 'و',
        'do', 'you', 'have', 'i', 'want', 'the', 'a', 'is', 'there', 'any', 'price', 'how', 'much',
    ];

    /** RRF constant from the original paper; damps the top ranks. */
    private const K = 60;

    /**
     * @return list<string> query words worth matching, in every search form
     */
    public static function words(string $query): array
    {
        $words = [];

        foreach (ArabicText::searchForms($query) as $form) {
            foreach (explode(' ', $form) as $word) {
                if (mb_strlen($word) >= 2 && ! in_array($word, self::FILLER, true)) {
                    $words[] = $word;
                }
            }
        }

        return array_values(array_unique($words));
    }

    /**
     * OR of the words for to_tsquery; normalized words hold only letters and digits.
     *
     * @param  list<string>  $words
     */
    public static function tsQuery(array $words): string
    {
        return implode(' | ', array_map(fn (string $word) => $word.':*', $words));
    }

    /**
     * @param  list<list<string>>  $rankings  ids, best first, one list per signal
     * @return array<string, float> id => fused score, best first
     */
    public static function fuse(array $rankings): array
    {
        $scores = [];

        foreach ($rankings as $ranking) {
            foreach ($ranking as $rank => $id) {
                $scores[$id] = ($scores[$id] ?? 0.0) + 1 / (self::K + $rank + 1);
            }
        }

        arsort($scores);

        return $scores;
    }

    /**
     * @param  array<mixed>  $ids  a pluck() result
     * @return list<string>
     */
    public static function strings(array $ids): array
    {
        return array_values(array_map(fn ($id) => (string) $id, $ids));
    }

    /**
     * @param  list<float>  $vector
     */
    public static function vectorLiteral(array $vector): string
    {
        return '['.implode(',', array_map(fn (float $value) => sprintf('%.7F', $value), $vector)).']';
    }
}
