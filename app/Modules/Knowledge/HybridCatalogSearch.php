<?php

namespace App\Modules\Knowledge;

use App\Modules\AiGateway\AiGateway;
use App\Modules\Knowledge\Data\CatalogHit;
use App\Modules\Knowledge\Models\ProductSearchEntry;

/**
 * Postgres FTS (prefix match) + trigram on each word + pgvector, fused by
 * rank. Names of perfumes and brands are rare words that embeddings get
 * wrong, so text signals come first; the vector adds meaning ("شي للمناسبات").
 */
final class HybridCatalogSearch implements CatalogSearch
{
    private const CANDIDATES = 20;

    /** Trigram floor per word: catches "شانل" for "شانيل", not noise. */
    private const TRIGRAM_FLOOR = 0.45;

    /** Cosine distance cap, so the nearest vector of an unrelated query is not a hit. [افتراض] tune on the 100-query set. */
    private const VECTOR_MAX_DISTANCE = 0.55;

    public function __construct(private readonly AiGateway $gateway) {}

    public function search(string $query, int $limit = 5): array
    {
        $words = HybridRanking::words($query);

        if ($words === []) {
            return [];
        }

        $rankings = [$this->fullText($words)];

        foreach ($words as $word) {
            if (mb_strlen($word) >= 3) {
                $rankings[] = $this->trigram($word);
            }
        }

        $rankings[] = $this->vector($query);

        $hits = [];

        foreach (array_slice(HybridRanking::fuse($rankings), 0, $limit, true) as $id => $score) {
            $hits[] = new CatalogHit((string) $id, round($score, 5));
        }

        return $hits;
    }

    /**
     * @param  list<string>  $words
     * @return list<string>
     */
    private function fullText(array $words): array
    {
        $tsQuery = HybridRanking::tsQuery($words);

        return ProductSearchEntry::query()
            ->whereRaw("tsv @@ to_tsquery('simple', ?)", [$tsQuery])
            ->orderByRaw("ts_rank(tsv, to_tsquery('simple', ?)) DESC, id", [$tsQuery])
            ->limit(self::CANDIDATES)
            ->pluck('product_external_id')
            ->pipe(fn ($ids) => HybridRanking::strings($ids->all()));
    }

    /**
     * @return list<string>
     */
    private function trigram(string $word): array
    {
        return ProductSearchEntry::query()
            ->whereRaw('word_similarity(?, search_text) >= ?', [$word, self::TRIGRAM_FLOOR])
            ->orderByRaw('word_similarity(?, search_text) DESC, id', [$word])
            ->limit(self::CANDIDATES)
            ->pluck('product_external_id')
            ->pipe(fn ($ids) => HybridRanking::strings($ids->all()));
    }

    /**
     * @return list<string>
     */
    private function vector(string $query): array
    {
        $vector = $this->gateway->embed([$query], 'catalog_search')[0] ?? null;

        if ($vector === null) {
            return [];
        }

        $literal = HybridRanking::vectorLiteral($vector);

        return ProductSearchEntry::query()
            ->whereNotNull('embedding')
            ->whereRaw('embedding <=> ?::vector <= ?', [$literal, self::VECTOR_MAX_DISTANCE])
            ->orderByRaw('embedding <=> ?::vector', [$literal])
            ->limit(self::CANDIDATES)
            ->pluck('product_external_id')
            ->pipe(fn ($ids) => HybridRanking::strings($ids->all()));
    }
}
