<?php

namespace App\Modules\Commerce\WooCommerce;

use App\Modules\Commerce\Data\CartData;
use App\Modules\Commerce\Data\CustomerData;
use App\Modules\Commerce\Data\LineItemData;
use App\Modules\Commerce\Data\OrderData;
use App\Modules\Commerce\Data\ProductData;
use App\Modules\Commerce\Data\VariantData;
use App\Shared\Money\Currencies;
use App\Shared\Money\Currency;
use App\Shared\Money\JsonAmount;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Tenancy\WorkspaceLocale;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * WooCommerce REST v3 orders/products and the CODEX Connector's Cart (protocol
 * §5) → our internal data (ADR-011). Amounts are decimal strings in the store
 * currency ("199.00", "12.345" for KWD), read exactly into minor units of the
 * workspace currency (ADR-012). Connecting sets the workspace currency from
 * the store; a cart or order in another currency is skipped by the callers
 * (inWorkspaceCurrency), never stored next to the others.
 *
 * [افتراض] Field shapes follow woocommerce.github.io/woocommerce-rest-api-docs
 * (v3) as read on 2026-10-25; confirm on the founder's site before a pilot.
 */
final class WooPayloadMapper
{
    private ?Currency $fixed = null;

    private ?int $tenantId = null;

    /** Mappers run inside a tenant: the currency is the workspace's. */
    public function __construct(private readonly ?WorkspaceLocale $locale = null) {}

    /** The same mapper for a store's workspace, whether or not its tenant is current. */
    public function forTenant(int $tenantId): self
    {
        $copy = clone $this;
        $copy->tenantId = $tenantId;

        return $copy;
    }

    /** The same mapper for a given currency (tests, tools without a tenant). */
    public function withCurrency(Currency $currency): self
    {
        $copy = clone $this;
        $copy->fixed = $currency;

        return $copy;
    }

    /**
     * A cart or order whose `currency` is not the workspace's (the store
     * setting changed after connecting): logged, and the caller skips it so
     * amounts in two currencies never mix. A row without one is the store's.
     *
     * @param  array<string, mixed>  $row
     */
    public function inWorkspaceCurrency(array $row, string $kind): bool
    {
        $code = $this->string(Arr::get($row, 'currency'));
        $workspace = $this->currency()->code;

        if ($code === null || strtoupper(trim($code)) === $workspace) {
            return true;
        }

        Log::warning('WooCommerce record in another currency skipped.', [
            'kind' => $kind,
            'id' => $this->string(Arr::get($row, 'id')),
            'currency' => strtoupper(trim($code)),
            'workspace_currency' => $workspace,
        ]);

        return false;
    }

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
            currency: $this->currency()->code,
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
                phone: PhoneNumber::tryParse($this->string(Arr::get($order, 'billing.phone')), $this->string(Arr::get($order, 'billing.country'))),
            ),
            totalHalalas: $this->halalas(Arr::get($order, 'total')),
            discountHalalas: $this->halalas(Arr::get($order, 'discount_total')),
            shippingHalalas: $this->halalas(Arr::get($order, 'shipping_total')),
            currency: $this->currency()->code,
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

    /** "199.00" → 19900 (SAR), "12.345" → 12345 (KWD); "" or missing → null (no price set). */
    private function money(mixed $amount): ?int
    {
        $decimal = JsonAmount::decimal($amount);

        if ($decimal === null) {
            return null;
        }

        try {
            return $this->currency()->toMinor($decimal);
        } catch (InvalidArgumentException) {
            // Beyond a bigint: no price rather than a wrong one, and the sync goes on.
            Log::warning('Store amount out of range ignored.', ['amount' => $decimal]);

            return null;
        }
    }

    private function currency(): Currency
    {
        return $this->fixed ?? $this->locale?->currency($this->tenantId) ?? Currencies::get((string) config('currencies.default'));
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
