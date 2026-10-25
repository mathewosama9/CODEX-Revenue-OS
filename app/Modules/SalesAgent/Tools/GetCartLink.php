<?php

namespace App\Modules\SalesAgent\Tools;

use App\Modules\AiGateway\AgentTool;
use App\Modules\Commerce\Commerce;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * The customer's own abandoned-cart checkout link, or a product page. No cart
 * is created and no payment link is made (09 §9.11.2, Rule 9). Nothing is
 * offered for an item that is out of stock (06 B.5.3).
 */
final class GetCartLink implements AgentTool
{
    public function __construct(
        private readonly Commerce $commerce,
        private readonly RunContext $context,
    ) {}

    public function name(): string
    {
        return 'get_cart_link';
    }

    public function description(): string
    {
        return 'Link to finish buying: the customer\'s saved cart checkout, or the page of one in-stock product.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['product_id' => $schema->string()->description('Optional: a product the customer wants, if they have no saved cart.')];
    }

    public function handle(array $arguments): array
    {
        $cart = $this->commerce->openCartFor($this->context->subjectHash);

        if ($cart !== null && $cart->checkoutUrl !== null) {
            $ids = array_values(array_filter(array_map(fn ($item) => $item->productExternalId, $cart->items)));
            $products = $this->commerce->products($ids);
            $allInStock = array_reduce($ids, fn (bool $ok, string $id) => $ok && ($products[$id] ?? null)?->inStock() === true, true);

            if ($allInStock) {
                return ['available' => true, 'type' => 'cart_checkout', 'url' => $cart->checkoutUrl];
            }
        }

        $product = isset($arguments['product_id']) ? $this->commerce->product((string) $arguments['product_id']) : null;

        if ($product !== null && $product->inStock() && $product->url !== null) {
            return ['available' => true, 'type' => 'product_page', 'url' => $product->url];
        }

        return ['available' => false];
    }
}
