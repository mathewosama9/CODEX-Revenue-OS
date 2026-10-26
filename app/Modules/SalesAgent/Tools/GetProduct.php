<?php

namespace App\Modules\SalesAgent\Tools;

use App\Modules\AiGateway\AgentTool;
use App\Modules\Commerce\Commerce;
use App\Shared\Money\Currency;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Current price and stock of one product: the only numbers the customer may
 * see (09 §9.11.2). A price older than the limit is refused, not shown.
 * Prices are exact major-unit strings of the workspace currency ("345.00",
 * "12.345", "25000"), never floats (ADR-012).
 */
final class GetProduct implements AgentTool
{
    /** Exact stock is shown only when it is low; otherwise "in stock". */
    private const LOW_STOCK = 5;

    private const DESCRIPTION_CHARS = 400;

    private const SPELLING = ['ليره' => 'ليرة', 'اوقيه' => 'أوقية'];

    public function __construct(
        private readonly Commerce $commerce,
        private readonly int $maxAgeMinutes,
        private readonly Currency $currency,
    ) {}

    public function name(): string
    {
        return 'get_product';
    }

    public function description(): string
    {
        return 'Live price (in `currency`, written with `currency_label`), stock and variants of one product by id. The only allowed source for any price or quantity in your reply.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['product_id' => $schema->string()->description('An id returned by search_products.')->required()];
    }

    public function handle(array $arguments): array
    {
        $product = $this->commerce->product((string) ($arguments['product_id'] ?? ''));

        if ($product === null || ! $product->listed()) {
            return ['found' => false];
        }

        if ($product->syncedAt === null || $product->syncedAt->lessThan(CarbonImmutable::now()->subMinutes($this->maxAgeMinutes))) {
            throw new PriceTooOld("Product {$product->externalId} price is stale.");
        }

        $inStock = $product->inStock();

        return array_filter([
            'found' => true,
            'product_id' => $product->externalId,
            'name' => $product->name,
            'category' => $product->categoryName,
            'currency' => $this->currency->code,
            'currency_label' => self::label($this->currency),
            'price' => $this->major($product->priceHalalas),
            // Shown only during a real sale, so the model never invents a "was" price.
            'was_price' => $product->regularPriceHalalas !== null && $product->priceHalalas !== null && $product->regularPriceHalalas > $product->priceHalalas
                ? $this->major($product->regularPriceHalalas) : null,
            'in_stock' => $inStock,
            'only_left' => $inStock && ! $product->unlimitedQuantity && $product->quantity !== null && $product->quantity <= self::LOW_STOCK ? $product->quantity : null,
            'variants' => array_map(fn (array $variant) => array_filter([
                'variant_id' => $variant['external_id'],
                'name' => $variant['name'],
                'price' => $this->major($variant['price_halalas']),
                'in_stock' => $inStock && ($variant['unlimited_quantity'] || $variant['quantity'] === null || $variant['quantity'] > 0),
            ], fn ($value) => $value !== null), $product->variants),
            'url' => $inStock ? $product->url : null,
            'description' => $product->description === null ? null : mb_substr($product->description, 0, self::DESCRIPTION_CHARS),
        ], fn ($value) => $value !== null && $value !== []);
    }

    /** Minor units → "345.00": exact, as the store shows it. */
    private function major(?int $minor): ?string
    {
        if ($minor === null) {
            return null;
        }

        // "345" reads better than "345.00"; "299.50" and "12.345" keep their fraction.
        $major = $this->currency->toMajor($minor);

        return preg_match('/\.0+$/', $major) === 1 ? (string) preg_replace('/\.0+$/', '', $major) : $major;
    }

    /**
     * The word the agent writes next to a price: «ريال», «جنيه», «دينار»;
     * the Arabic symbol when the currency has no word (e.g. "$").
     */
    public static function label(Currency $currency): string
    {
        foreach ($currency->words as $word) {
            if (preg_match('/^\p{Arabic}{3,}$/u', $word) === 1) {
                // The config words are normalized (ة→ه, أ→ا); write them properly.
                return self::SPELLING[$word] ?? $word;
            }
        }

        return $currency->symbol('ar');
    }
}
