<?php

namespace App\Modules\Knowledge;

use App\Modules\AiGateway\AiGateway;
use App\Modules\Commerce\Commerce;
use App\Modules\Commerce\Data\ProductSnapshot;
use App\Modules\Knowledge\Models\ProductSearchEntry;
use App\Shared\Arabic\ArabicText;
use Illuminate\Support\Facades\DB;

/**
 * Keeps `product_search_index` in step with the catalog. A hidden or deleted
 * product leaves the index at once (US-E09-05); unchanged text is not
 * embedded again, so price-only updates cost nothing.
 */
final class CatalogIndexer
{
    private const DESCRIPTION_CHARS = 800;

    public function __construct(
        private readonly Commerce $commerce,
        private readonly AiGateway $gateway,
    ) {}

    public function index(string $externalId): void
    {
        $product = $this->commerce->product($externalId);

        if ($product === null || ! $product->listed()) {
            ProductSearchEntry::query()->where('product_external_id', $externalId)->delete();

            return;
        }

        $this->upsert([$product]);
    }

    /**
     * The nightly safety net (09 §9.12): every listed product, embeddings in
     * batches, stale entries removed.
     */
    public function reindexAll(): int
    {
        $listed = [];
        $batch = [];

        foreach ($this->commerce->catalog() as $product) {
            if (! $product->listed()) {
                continue;
            }

            $listed[] = $product->externalId;
            $batch[] = $product;

            if (count($batch) === 50) {
                $this->upsert($batch);
                $batch = [];
            }
        }

        $this->upsert($batch);

        ProductSearchEntry::query()->whereNotIn('product_external_id', $listed === [] ? [''] : $listed)->delete();

        return count($listed);
    }

    /**
     * @param  list<ProductSnapshot>  $products
     */
    private function upsert(array $products): void
    {
        $toEmbed = [];

        foreach ($products as $product) {
            $text = ArabicText::normalize(self::document($product));
            $hash = hash('sha256', $text);
            $entry = ProductSearchEntry::query()->firstOrNew(['product_external_id' => $product->externalId]);

            if ($entry->exists && $entry->content_hash === $hash && $entry->embedded_at !== null) {
                continue;
            }

            $entry->fill(['search_text' => $text, 'content_hash' => $hash, 'embedded_at' => null])->save();
            $toEmbed[$entry->id] = self::document($product);
        }

        $vectors = $this->gateway->embed(array_values($toEmbed), 'catalog_index');

        foreach (array_keys($toEmbed) as $i => $entryId) {
            if (isset($vectors[$i])) {
                DB::table('product_search_index')->where('id', $entryId)->update([
                    'embedding' => HybridRanking::vectorLiteral($vectors[$i]),
                    'embedded_at' => now(),
                ]);
            }
        }
    }

    /** What the index knows: words, never prices or stock. */
    private static function document(ProductSnapshot $product): string
    {
        return trim(implode(' ', array_filter([
            $product->name,
            $product->sku,
            $product->categoryName,
            implode(' ', array_column($product->variants, 'name')),
            mb_substr((string) $product->description, 0, self::DESCRIPTION_CHARS),
        ])));
    }
}
