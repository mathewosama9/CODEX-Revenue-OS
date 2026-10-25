<?php

namespace App\Modules\SalesAgent\Tools;

use App\Modules\AiGateway\AgentTool;
use App\Modules\Commerce\Commerce;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Current price and stock of one product: the only numbers the customer may
 * see (09 §9.11.2). A price older than the limit is refused, not shown.
 */
final class GetProduct implements AgentTool
{
    /** Exact stock is shown only when it is low; otherwise "in stock". */
    private const LOW_STOCK = 5;

    private const DESCRIPTION_CHARS = 400;

    public function __construct(
        private readonly Commerce $commerce,
        private readonly int $maxAgeMinutes,
    ) {}

    public function name(): string
    {
        return 'get_product';
    }

    public function description(): string
    {
        return 'Live price (SAR), stock and variants of one product by id. The only allowed source for any price or quantity in your reply.';
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
            'currency' => 'SAR',
            'price_sar' => self::sar($product->priceHalalas),
            // Shown only during a real sale, so the model never invents a "was" price.
            'was_price_sar' => $product->regularPriceHalalas !== null && $product->priceHalalas !== null && $product->regularPriceHalalas > $product->priceHalalas
                ? self::sar($product->regularPriceHalalas) : null,
            'in_stock' => $inStock,
            'only_left' => $inStock && ! $product->unlimitedQuantity && $product->quantity !== null && $product->quantity <= self::LOW_STOCK ? $product->quantity : null,
            'variants' => array_map(fn (array $variant) => array_filter([
                'variant_id' => $variant['external_id'],
                'name' => $variant['name'],
                'price_sar' => self::sar($variant['price_halalas']),
                'in_stock' => $inStock && ($variant['unlimited_quantity'] || $variant['quantity'] === null || $variant['quantity'] > 0),
            ], fn ($value) => $value !== null), $product->variants),
            'url' => $inStock ? $product->url : null,
            'description' => $product->description === null ? null : mb_substr($product->description, 0, self::DESCRIPTION_CHARS),
        ], fn ($value) => $value !== null && $value !== []);
    }

    private static function sar(?int $halalas): ?float
    {
        return $halalas === null ? null : round($halalas / 100, 2);
    }
}
