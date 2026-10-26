<?php

namespace App\Modules\Commerce\Salla;

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
 * Salla payloads → our internal data (ADR-011). Salla changes field names with
 * two-week notice (e.g. checkout_url → urls.checkout, May 2025), so every
 * lookup tolerates the known variants and lives only here.
 *
 * [افتراض] Field shapes follow docs.salla.dev as read on 2026-10-21 and must be
 * confirmed against the demo store in the P0b spike.
 *
 * Amounts are read exactly into minor units of the workspace currency
 * (ADR-012). Salla stores sell in SAR in practice; an amount object in
 * another currency is logged and still read as the workspace's.
 */
final class SallaPayloadMapper
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
     * @param  array<string, mixed>  $cart
     */
    public function cart(array $cart): CartData
    {
        return new CartData(
            externalId: (string) Arr::get($cart, 'id'),
            customer: $this->customer(Arr::get($cart, 'customer')),
            totalHalalas: $this->halalas(Arr::get($cart, 'total')),
            currency: $this->currencyOf(Arr::get($cart, 'total'), 'cart', Arr::get($cart, 'id')),
            checkoutUrl: $this->string(Arr::get($cart, 'urls.checkout') ?? Arr::get($cart, 'checkout_url')),
            items: array_map(fn (array $item) => $this->lineItem($item), $this->list(Arr::get($cart, 'items'))),
            abandonedAt: $this->date(Arr::get($cart, 'updated_at') ?? Arr::get($cart, 'created_at')),
            purchased: Arr::get($cart, 'status') === 'purchased',
        );
    }

    /**
     * @param  array<string, mixed>  $order
     */
    public function order(array $order): OrderData
    {
        $status = $this->string(Arr::get($order, 'status.slug') ?? Arr::get($order, 'status.name') ?? Arr::get($order, 'status'));

        return new OrderData(
            externalId: (string) Arr::get($order, 'id'),
            customer: $this->customer(Arr::get($order, 'customer')),
            totalHalalas: $this->halalas(Arr::get($order, 'amounts.total') ?? Arr::get($order, 'total')),
            discountHalalas: $this->discounts($order),
            shippingHalalas: $this->halalas(Arr::get($order, 'amounts.shipping_cost')),
            currency: $this->currencyOf(Arr::get($order, 'amounts.total') ?? Arr::get($order, 'total'), 'order', Arr::get($order, 'id')),
            status: $status,
            paymentMethod: $this->string(Arr::get($order, 'payment_method')),
            couponCode: $this->string(Arr::get($order, 'coupon.code') ?? Arr::get($order, 'coupon')),
            placedAt: $this->date(Arr::get($order, 'date') ?? Arr::get($order, 'created_at')) ?? CarbonImmutable::now(),
            items: array_map(fn (array $item) => $this->lineItem($item), $this->list(Arr::get($order, 'items'))),
            cancelled: in_array($status, ['canceled', 'cancelled'], true),
            refundedHalalas: $this->halalas(Arr::get($order, 'amounts.refunded') ?? Arr::get($order, 'refund_amount')),
        );
    }

    /**
     * @param  array<string, mixed>  $product
     */
    public function product(array $product): ProductData
    {
        $categories = $this->list(Arr::get($product, 'categories'));
        $regular = $this->money(Arr::get($product, 'regular_price'));
        $current = $this->money(Arr::get($product, 'price'));

        return new ProductData(
            externalId: (string) Arr::get($product, 'id'),
            name: (string) (Arr::get($product, 'name') ?? ''),
            sku: $this->string(Arr::get($product, 'sku')),
            categoryExternalId: $this->string($categories[0]['id'] ?? null),
            priceHalalas: $this->sellingPrice($current, $this->money(Arr::get($product, 'sale_price')), $regular),
            quantity: is_numeric(Arr::get($product, 'quantity')) ? (int) Arr::get($product, 'quantity') : null,
            status: $this->string(Arr::get($product, 'status')),
            description: $this->plainText(Arr::get($product, 'description')),
            url: $this->string(Arr::get($product, 'urls.customer') ?? Arr::get($product, 'url')),
            regularPriceHalalas: $regular ?? $current,
            isAvailable: is_bool(Arr::get($product, 'is_available')) ? Arr::get($product, 'is_available') : null,
            unlimitedQuantity: Arr::get($product, 'unlimited_quantity') === true,
            variants: $this->variants($product),
        );
    }

    /**
     * Salla `price` is the current price; `sale_price` is set (non-zero, below
     * the regular price) only during a sale (docs.salla.dev ProductDetails,
     * read 2026-10-25). [افتراض] Confirm on the demo store which of the two
     * the storefront shows during a sale.
     */
    private function sellingPrice(?int $current, ?int $sale, ?int $regular): ?int
    {
        if ($sale !== null && $sale > 0 && ($regular === null || $sale < $regular)) {
            return $sale;
        }

        return $current;
    }

    /**
     * Variants (`skus`) carry option value ids; the names are on the product's
     * `options[].values[]` (docs.salla.dev, read 2026-10-25).
     *
     * @param  array<string, mixed>  $product
     * @return list<VariantData>
     */
    private function variants(array $product): array
    {
        $names = [];

        foreach ($this->list(Arr::get($product, 'options')) as $option) {
            foreach ($this->list($option['values'] ?? null) as $value) {
                $names[(string) ($value['id'] ?? '')] = (string) ($value['name'] ?? '');
            }
        }

        return array_map(function (array $sku) use ($names) {
            $label = array_filter(array_map(
                fn ($id) => $names[(string) $id] ?? null,
                (array) ($sku['related_option_values'] ?? []),
            ));
            $regular = $this->money($sku['regular_price'] ?? null);

            return new VariantData(
                externalId: (string) ($sku['id'] ?? ''),
                name: $label === [] ? (string) ($sku['sku'] ?? '') : implode(' / ', $label),
                priceHalalas: $this->sellingPrice($this->money($sku['price'] ?? null), $this->money($sku['sale_price'] ?? null), $regular),
                quantity: is_numeric($sku['stock_quantity'] ?? null) ? (int) $sku['stock_quantity'] : null,
                unlimitedQuantity: ($sku['unlimited_quantity'] ?? false) === true,
            );
        }, $this->list(Arr::get($product, 'skus')));
    }

    /** 249.5, "249.50" or {"amount": 249.5} → minor units; null when there is no number. */
    private function money(mixed $amount): ?int
    {
        if (is_array($amount)) {
            $amount = $amount['amount'] ?? null;
        }

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

    /** Product descriptions are HTML; the search and the agent read text. */
    private function plainText(mixed $html): ?string
    {
        if (! is_string($html) || trim($html) === '') {
            return null;
        }

        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", $html)), ENT_QUOTES | ENT_HTML5);

        return mb_substr(trim((string) preg_replace('/[ \t]+/u', ' ', $text)), 0, 4000);
    }

    /**
     * Salla mobiles come either whole (+966…) or split into mobile_code + mobile.
     */
    public function customer(mixed $customer): ?CustomerData
    {
        if (! is_array($customer)) {
            return null;
        }

        $mobile = $this->string(Arr::get($customer, 'mobile'));
        $code = $this->string(Arr::get($customer, 'mobile_code'));

        if ($mobile !== null && $code !== null && ! str_starts_with($mobile, '+')) {
            $mobile = '+'.ltrim($code, '+').ltrim($mobile, '0');
        }

        return new CustomerData(
            externalId: $this->string(Arr::get($customer, 'id')),
            phone: PhoneNumber::tryParse($mobile),
        );
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function lineItem(array $item): LineItemData
    {
        return new LineItemData(
            productExternalId: $this->string(Arr::get($item, 'product_id') ?? Arr::get($item, 'product.id')),
            name: $this->string(Arr::get($item, 'name') ?? Arr::get($item, 'product.name')),
            quantity: max(1, (int) (Arr::get($item, 'quantity') ?? 1)),
            unitPriceHalalas: $this->halalas(
                Arr::get($item, 'amounts.price_without_tax') ?? Arr::get($item, 'amounts.price') ?? Arr::get($item, 'price'),
            ),
            categoryExternalId: $this->string(Arr::get($item, 'product.category.id') ?? Arr::get($item, 'category_id')),
        );
    }

    /**
     * @param  array<string, mixed>  $order
     */
    private function discounts(array $order): int
    {
        $discounts = Arr::get($order, 'amounts.discounts');

        if (is_array($discounts) && array_is_list($discounts)) {
            return array_sum(array_map(fn ($discount) => $this->halalas(is_array($discount) ? ($discount['discount'] ?? $discount['amount'] ?? 0) : $discount), $discounts));
        }

        return $this->halalas($discounts ?? Arr::get($order, 'total_discount'));
    }

    /**
     * Amounts arrive as 12.5, "12.50" or {"amount": 12.5, "currency": "SAR"}.
     */
    private function halalas(mixed $amount): int
    {
        return $this->money($amount) ?? 0;
    }

    /**
     * The workspace currency. An amount object in another one is logged, not
     * refused: the amounts are still stored as the workspace's minor units.
     */
    private function currencyOf(mixed $amount, string $kind, mixed $id): string
    {
        $workspace = $this->currency()->code;
        $sent = is_array($amount) && is_string($amount['currency'] ?? null) ? strtoupper(trim($amount['currency'])) : null;

        if ($sent !== null && $sent !== '' && $sent !== $workspace) {
            Log::warning('Salla amount in another currency read as the workspace currency.', [
                'kind' => $kind,
                'id' => $this->string($id),
                'currency' => $sent,
                'workspace_currency' => $workspace,
            ]);
        }

        return $workspace;
    }

    private function currency(): Currency
    {
        return $this->fixed ?? $this->locale?->currency($this->tenantId) ?? Currencies::get((string) config('currencies.default'));
    }

    /**
     * Dates arrive as strings or as {"date": "...", "timezone": "Asia/Riyadh"}.
     */
    private function date(mixed $value): ?CarbonImmutable
    {
        $timezone = 'Asia/Riyadh';

        if (is_array($value)) {
            $timezone = is_string($value['timezone'] ?? null) ? $value['timezone'] : $timezone;
            $value = $value['date'] ?? null;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        // A malformed date must not fail the whole webhook; the recorder falls back to now().
        try {
            return CarbonImmutable::parse($value, $timezone)->utc();
        } catch (Throwable) {
            return null;
        }
    }

    private function string(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function list(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, 'is_array')) : [];
    }
}
