<?php

namespace App\Modules\SalesAgent\Tools;

use App\Modules\AiGateway\AgentTool;
use App\Modules\Commerce\Commerce;
use App\Modules\Knowledge\CatalogSearch;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Finds products of this store. Names and ids only: prices come from
 * `get_product`, the single source of numbers (09 §9.11.2).
 */
final class SearchProducts implements AgentTool
{
    public function __construct(
        private readonly CatalogSearch $search,
        private readonly Commerce $commerce,
    ) {}

    public function name(): string
    {
        return 'search_products';
    }

    public function description(): string
    {
        return 'Search this store\'s catalog by name, brand, notes or occasion. Returns product ids and names only; call get_product for price and stock.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['query' => $schema->string()->description('What the customer is looking for, in their words.')->required()];
    }

    public function handle(array $arguments): array
    {
        $hits = $this->search->search(mb_substr((string) ($arguments['query'] ?? ''), 0, 200), 5);
        $products = $this->commerce->products(array_map(fn ($hit) => $hit->productExternalId, $hits));
        $results = [];

        foreach ($hits as $hit) {
            $product = $products[$hit->productExternalId] ?? null;

            if ($product !== null && $product->listed()) {
                $results[] = ['product_id' => $product->externalId, 'name' => $product->name, 'category' => $product->categoryName];
            }
        }

        return ['results' => $results];
    }
}
