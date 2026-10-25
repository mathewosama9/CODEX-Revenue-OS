<?php

namespace App\Modules\Knowledge;

use App\Modules\AiGateway\AiGateway;
use App\Modules\Knowledge\Data\PolicyPassage;
use App\Modules\Knowledge\Models\KnowledgeChunk;
use App\Modules\Knowledge\Models\KnowledgeDocument;
use App\Shared\Arabic\ArabicText;
use Illuminate\Support\Facades\DB;

/**
 * The merchant's FAQ, shipping and returns text (US-E09-02): saving a
 * document re-chunks and re-embeds it; retrieval returns the merchant's own
 * words with a reference to the document.
 */
final class KnowledgeBase implements KnowledgeRetriever
{
    private const CHUNK_CHARS = 700;

    public function __construct(private readonly AiGateway $gateway) {}

    public function save(?KnowledgeDocument $document, KnowledgeKind $kind, string $title, string $body, int $userId): KnowledgeDocument
    {
        $document ??= new KnowledgeDocument;
        $document->fill(['kind' => $kind, 'title' => $title, 'body' => $body, 'updated_by' => $userId])->save();

        $this->rechunk($document);

        return $document;
    }

    public function delete(KnowledgeDocument $document): void
    {
        $document->delete();
    }

    public function retrieve(KnowledgeKind $kind, ?string $question = null, int $limit = 3): array
    {
        $chunks = KnowledgeChunk::query()
            ->with('document')
            ->whereHas('document', fn ($query) => $query->where('kind', $kind))
            ->orderBy('document_id')
            ->orderBy('position')
            ->get();

        if ($chunks->isEmpty()) {
            return [];
        }

        // Shipping and returns are short: give the whole policy. The FAQ is ranked by the question.
        if ($kind !== KnowledgeKind::Faq || $question === null || trim($question) === '') {
            return array_values($chunks->take($limit * 3)->map(fn (KnowledgeChunk $chunk) => self::passage($chunk))->all());
        }

        $words = HybridRanking::words($question);
        $ids = $chunks->pluck('id')->all();
        $rankings = [];

        if ($words !== []) {
            $tsQuery = HybridRanking::tsQuery($words);
            $rankings[] = KnowledgeChunk::query()->whereIn('id', $ids)
                ->whereRaw("tsv @@ to_tsquery('simple', ?)", [$tsQuery])
                ->orderByRaw("ts_rank(tsv, to_tsquery('simple', ?)) DESC, id", [$tsQuery])
                ->pluck('id')->pipe(fn ($found) => HybridRanking::strings($found->all()));
        }

        $vector = $this->gateway->embed([$question], 'knowledge_search')[0] ?? null;

        if ($vector !== null) {
            $literal = HybridRanking::vectorLiteral($vector);
            $rankings[] = KnowledgeChunk::query()->whereIn('id', $ids)->whereNotNull('embedding')
                ->orderByRaw('embedding <=> ?::vector', [$literal])
                ->limit($limit * 2)
                ->pluck('id')->pipe(fn ($found) => HybridRanking::strings($found->all()));
        }

        $ranked = array_keys(HybridRanking::fuse($rankings));
        $byId = $chunks->keyBy('id');

        return array_map(
            fn ($id) => self::passage($byId[(int) $id]),
            array_slice($ranked, 0, $limit),
        );
    }

    private function rechunk(KnowledgeDocument $document): void
    {
        $document->chunks()->delete();
        $chunks = [];

        foreach (self::split($document->body) as $position => $content) {
            $chunks[] = KnowledgeChunk::create([
                'document_id' => $document->id,
                'position' => $position,
                'content' => $content,
                'search_text' => ArabicText::normalize($document->title.' '.$content),
            ]);
        }

        $vectors = $this->gateway->embed(array_map(fn (KnowledgeChunk $chunk) => $document->title."\n".$chunk->content, $chunks), 'knowledge_index');

        foreach ($chunks as $i => $chunk) {
            if (isset($vectors[$i])) {
                DB::table('knowledge_chunks')->where('id', $chunk->id)->update([
                    'embedding' => HybridRanking::vectorLiteral($vectors[$i]),
                    'embedded_at' => now(),
                ]);
            }
        }
    }

    /**
     * Paragraphs, merged up to ~700 characters, never split mid-paragraph.
     *
     * @return list<string>
     */
    private static function split(string $body): array
    {
        $paragraphs = array_values(array_filter(array_map('trim', preg_split('/\n\s*\n/u', $body) ?: []), fn ($p) => $p !== ''));
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $paragraph) {
            if ($current !== '' && mb_strlen($current) + mb_strlen($paragraph) > self::CHUNK_CHARS) {
                $chunks[] = $current;
                $current = '';
            }

            $current = $current === '' ? $paragraph : $current."\n\n".$paragraph;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    private static function passage(KnowledgeChunk $chunk): PolicyPassage
    {
        return new PolicyPassage($chunk->document_id, $chunk->document->kind, $chunk->document->title, $chunk->content);
    }
}
