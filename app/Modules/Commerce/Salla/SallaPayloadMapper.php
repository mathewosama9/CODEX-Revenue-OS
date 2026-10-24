<?php

namespace App\Modules\Commerce\Salla;

use App\Modules\Commerce\Data\CartData;
use App\Modules\Commerce\Data\CustomerData;
use App\Modules\Commerce\Data\LineItemData;
use App\Modules\Commerce\Data\OrderData;
use App\Modules\Commerce\Data\ProductData;
use App\Shared\Phone\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Throwable;

/**
 * Salla payloads → our internal data (ADR-011). Salla changes field names with
 * two-week notice (e.g. checkout_url → urls.checkout, May 2025), so every
 * lookup tolerates the known variants and lives only here.
 *
 * [افتراض] Field shapes follow docs.salla.dev as read on 2026-10-21 and must be
 * confirmed against the demo store in the P0b spike.
 */
final class SallaPayloadMapper
{
    /**
     * @param  array<string, mixed>  $cart
     */
    public function cart(array $cart): CartData
    {
        return new CartData(
            externalId: (string) Arr::get($cart, 'id'),
            customer: $this->customer(Arr::get($cart, 'customer')),
            totalHalalas: $this->halalas(Arr::get($cart, 'total')),
            currency: $this->currency(Arr::get($cart, 'total')),
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
            currency: $this->currency(Arr::get($order, 'amounts.total') ?? Arr::get($order, 'total')),
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

        return new ProductData(
            externalId: (string) Arr::get($product, 'id'),
            name: (string) (Arr::get($product, 'name') ?? ''),
            sku: $this->string(Arr::get($product, 'sku')),
            categoryExternalId: $this->string($categories[0]['id'] ?? null),
            priceHalalas: Arr::has($product, 'price') ? $this->halalas(Arr::get($product, 'price')) : null,
            quantity: is_numeric(Arr::get($product, 'quantity')) ? (int) Arr::get($product, 'quantity') : null,
            status: $this->string(Arr::get($product, 'status')),
        );
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
        if (is_array($amount)) {
            $amount = $amount['amount'] ?? 0;
        }

        return is_numeric($amount) ? (int) round(((float) $amount) * 100) : 0;
    }

    private function currency(mixed $amount): string
    {
        return is_array($amount) && is_string($amount['currency'] ?? null) ? $amount['currency'] : 'SAR';
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
