<?php

namespace App\Modules\Commerce\WooCommerce;

use App\Modules\Commerce\Data\CartData;
use App\Modules\Commerce\Data\CustomerData;
use App\Modules\Commerce\Data\LineItemData;
use App\Modules\Commerce\Data\OrderData;
use App\Modules\Commerce\Data\ProductData;
use App\Modules\Commerce\Data\VariantData;
use App\Shared\Phone\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Throwable;

/**
 * WooCommerce REST v3 orders/products and the CODEX Connector's Cart (protocol
 * §5) → our internal data (ADR-011). Amounts are decimal strings in the store
 * currency ("199.00"); the connect step refuses any store not in SAR.
 *
 * [افتراض] Field shapes follow woocommerce.github.io/woocommerce-rest-api-docs
 * (v3) as read on 2026-10-25; confirm on the founder's site before a pilot.
 */
final class WooPayloadMapper
{
    /**
     * The plugin sends the phone only with consent; without it we drop the
     * phone even if one slipped through.
     *
     * @param  array<string, mixed>  $cart
     */
    public function cart(array $cart): CartData
    {
        $consented = Arr::get($cart, 'consent') === true;

        return new CartData(
            externalId: (string) Arr::get($cart, 'id'),
            customer: new CustomerData(
                externalId: $this->string(Arr::get($cart, 'customer_id')),
                phone: $consented ? PhoneNumber::tryParse($this->string(Arr::get($cart, 'phone'))) : null,
            ),
            totalHalalas: $this->halalas(Arr::get($cart, 'total')),
            currency: $this->string(Arr::get($cart, 'currency')) ?? 'SAR',
            checkoutUrl: $this->url(Arr::get($cart, 'recovery_url')),
            items: array_map(fn (array $item) => new LineItemData(
                productExternalId: $this->string($item['product_id'] ?? null),
                name: $this->string($item['name'] ?? null),
                quantity: max(1, (int) ($item['quantity'] ?? 1)),
                unitPriceHalalas: $this->halalas($item['unit_price'] ?? null),
                categoryExternalId: $this->string($item['category_id'] ?? null),
            ), $this->list(Arr::get($cart, 'items'))),
            abandonedAt: $this->date(Arr::get($cart, 'abandoned_at') ?? Arr::get($cart, 'updated_at')),
            purchased: Arr::get($cart, 'status') === 'purchased',
        );
    }

    /**
     * @param  array<string, mixed>  $order
     */
    public function order(array $order): OrderData
    {
        $status = $this->string(Arr::get($order, 'status'));
        $customerId = Arr::get($order, 'customer_id');

        return new OrderData(
            externalId: (string) Arr::get($order, 'id'),
            customer: new CustomerData(
                externalId: is_numeric($customerId) && (int) $customerId > 0 ? (string) $customerId : null,
                phone: PhoneNumber::tryParse($this->string(Arr::get($order, 'billing.phone'))),
            ),
            totalHalalas: $this->halalas(Arr::get($order, 'total')),
            discountHalalas: $this->halalas(Arr::get($order, 'discount_total')),
            shippingHalalas: $this->halalas(Arr::get($order, 'shipping_total')),
            currency: $this->string(Arr::get($order, 'currency')) ?? 'SAR',
            status: $status,
            paymentMethod: $this->string(Arr::get($order, 'payment_method')),
            couponCode: $this->string(Arr::get($this->list(Arr::get($order, 'coupon_lines')), '0.code')),
            placedAt: $this->date(Arr::get($order, 'date_created_gmt'))
                ?? $this->date(Arr::get($order, 'date_created'))
                ?? CarbonImmutable::now(),
            items: array_map(fn (array $item) => new LineItemData(
                productExternalId: $this->string($item['product_id'] ?? null),
                name: $this->string($item['name'] ?? null),
                quantity: max(1, (int) ($item['quantity'] ?? 1)),
                unitPriceHalalas: $this->halalas($item['price'] ?? null),
            ), $this->list(Arr::get($order, 'line_items'))),
            cancelled: in_array($status, ['cancelled', 'failed', 'trash'], true),
            refundedHalalas: array_sum(array_map(
                fn (array $refund) => abs($this->halalas($refund['total'] ?? null)),
                $this->list(Arr::get($order, 'refunds')),
            )),
        );
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  list<array<string, mixed>>  $variations  `products/{id}/variations` of a variable product
     */
    public function product(array $product, array $variations = []): ProductData
    {
        $categories = $this->list(Arr::get($product, 'categories'));
        $regular = $this->money(Arr::get($product, 'regular_price'));
        $current = $this->money(Arr::get($product, 'price'));
        $stock = $this->stock($product);

        return new ProductData(
            externalId: (string) Arr::get($product, 'id'),
            name: $this->text(Arr::get($product, 'name')) ?? '',
            sku: $this->string(Arr::get($product, 'sku')),
            categoryExternalId: $this->string($categories[0]['id'] ?? null),
            priceHalalas: $this->sellingPrice($current, $this->money(Arr::get($product, 'sale_price')), $regular),
            quantity: $stock['quantity'],
            status: $this->status($product),
            description: $this->plainText(Arr::get($product, 'description')) ?? $this->plainText(Arr::get($product, 'short_description')),
            url: $this->url(Arr::get($product, 'permalink')),
            regularPriceHalalas: $regular ?? $current,
            isAvailable: $stock['available'],
            unlimitedQuantity: $stock['unlimited'],
            variants: array_map(fn (array $variation) => $this->variant($variation), $variations),
        );
    }

    /**
     * Only published products are offered; out of stock is "out", like Salla.
     *
     * @param  array<string, mixed>  $product
     */
    private function status(array $product): string
    {
        if (Arr::get($product, 'status') !== 'publish') {
            return 'hidden';
        }

        return Arr::get($product, 'stock_status') === 'outofstock' ? 'out' : 'available';
    }

    /**
     * `manage_stock` off: Woo only knows in stock or not. Back-orders can
     * always be bought, whatever the count says.
     *
     * @param  array<string, mixed>  $item
     * @return array{quantity: int|null, available: bool, unlimited: bool}
     */
    private function stock(array $item): array
    {
        $status = Arr::get($item, 'stock_status');
        $managed = Arr::get($item, 'manage_stock') === true;
        $quantity = Arr::get($item, 'stock_quantity');

        return [
            'quantity' => $managed && is_numeric($quantity) ? (int) $quantity : null,
            'available' => $status !== 'outofstock',
            'unlimited' => $status === 'onbackorder' || (! $managed && $status !== 'outofstock'),
        ];
    }

    /**
     * @param  array<string, mixed>  $variation
     */
    private function variant(array $variation): VariantData
    {
        $options = array_filter(array_map(
            fn (array $attribute) => $this->string($attribute['option'] ?? null),
            $this->list(Arr::get($variation, 'attributes')),
        ));
        $stock = $this->stock($variation);
        $regular = $this->money(Arr::get($variation, 'regular_price'));

        return new VariantData(
            externalId: (string) Arr::get($variation, 'id'),
            name: $options === [] ? (string) ($this->string(Arr::get($variation, 'sku')) ?? Arr::get($variation, 'id')) : implode(' / ', $options),
            priceHalalas: $this->sellingPrice($this->money(Arr::get($variation, 'price')), $this->money(Arr::get($variation, 'sale_price')), $regular),
            // VariantData has no "available" flag: an out-of-stock variation reads as none left.
            quantity: $stock['available'] ? $stock['quantity'] : 0,
            unlimitedQuantity: $stock['unlimited'],
        );
    }

    /**
     * Woo `price` is what the shop shows now; `sale_price` is set ("" when
     * not) during a sale. Same rule as the Salla mapper.
     */
    private function sellingPrice(?int $current, ?int $sale, ?int $regular): ?int
    {
        if ($sale !== null && $sale > 0 && ($regular === null || $sale < $regular)) {
            return $sale;
        }

        return $current;
    }

    /** "199.00" → 19900; "" or missing → null (no price set). */
    private function money(mixed $amount): ?int
    {
        return is_numeric($amount) ? (int) round(((float) $amount) * 100) : null;
    }

    private function halalas(mixed $amount): int
    {
        return $this->money($amount) ?? 0;
    }

    /** Product descriptions are HTML; the search and the agent read text. */
    private function plainText(mixed $html): ?string
    {
        if (! is_string($html) || trim($html) === '') {
            return null;
        }

        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $html)), ENT_QUOTES | ENT_HTML5);
        $text = mb_substr(trim((string) preg_replace('/[ \t]+/u', ' ', $text)), 0, 4000);

        return $text === '' ? null : $text;
    }

    /** Woo names come HTML-escaped ("Oud &amp; Musk"). */
    private function text(mixed $value): ?string
    {
        $value = $this->string($value);

        return $value === null ? null : html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
    }

    /** Links we put in messages are HTTP(S) only. */
    private function url(mixed $value): ?string
    {
        $value = $this->string($value);

        return $value !== null && preg_match('#^https?://#i', $value) === 1 ? $value : null;
    }

    /**
     * `*_gmt` dates come without a zone ("2026-10-28T10:15:00"): read them as UTC.
     */
    private function date(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        // A malformed date must not fail the whole webhook; the recorder falls back to now().
        try {
            return CarbonImmutable::parse($value, 'UTC')->utc();
        } catch (Throwable) {
            return null;
        }
    }

    private function string(mixed $value): ?string
    {
        return is_scalar($value) && ! is_bool($value) && (string) $value !== '' ? (string) $value : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function list(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }
}
