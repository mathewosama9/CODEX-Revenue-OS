<?php

namespace App\Modules\Commerce;

use App\Modules\Commerce\Data\CartData;
use App\Modules\Commerce\Data\CustomerData;
use App\Modules\Commerce\Data\LineItemData;
use App\Modules\Commerce\Data\OrderData;
use App\Modules\Commerce\Data\ProductData;
use App\Modules\Commerce\Events\CartAbandoned;
use App\Modules\Commerce\Events\CartPurchased;
use App\Modules\Commerce\Events\OrderCancelled;
use App\Modules\Commerce\Events\OrderPlaced;
use App\Modules\Commerce\Events\OrderRefunded;
use App\Modules\Commerce\Events\ProductChanged;
use App\Modules\Commerce\Models\Cart;
use App\Modules\Commerce\Models\Customer;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\Product;
use App\Shared\Phone\SubjectHasher;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Writes platform data into our internal model (idempotent upserts by external
 * id) and announces what changed. Runs inside the tenant context.
 */
class CommerceRecorder
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly SubjectHasher $hasher,
    ) {}

    /**
     * @return bool true when this is a cart we did not know about yet
     */
    public function recordAbandonedCart(CartData $data, DiscoveredBy $discoveredBy): bool
    {
        if ($data->purchased) {
            $this->markCartPurchased($data->externalId);

            return false;
        }

        return DB::transaction(function () use ($data, $discoveredBy) {
            $customer = $this->customer($data->customer);

            $cart = Cart::query()->firstOrNew(['external_id' => $data->externalId]);
            $isNew = ! $cart->exists;

            if (! $isNew && $cart->status === CartStatus::Purchased) {
                return false;
            }

            $cart->fill([
                'customer_id' => $customer?->id,
                'phone_hash' => $customer?->phone_hash,
                'total_halalas' => $data->totalHalalas,
                'currency' => $data->currency,
                'item_count' => array_sum(array_map(fn (LineItemData $item) => $item->quantity, $data->items)),
                'checkout_url' => $data->checkoutUrl,
                'status' => CartStatus::Abandoned,
                'abandoned_at' => $cart->abandoned_at ?? $data->abandonedAt ?? now(),
            ]);

            if ($isNew) {
                $cart->discovered_by = $discoveredBy;
            }

            $cart->save();

            $cart->items()->delete();
            $cart->items()->createMany(array_map(fn (LineItemData $item) => [
                'product_external_id' => $item->productExternalId,
                'name' => $item->name,
                'quantity' => $item->quantity,
                'unit_price_halalas' => $item->unitPriceHalalas,
            ], $data->items));

            // Backfilled carts are history: they must never start a recovery sequence.
            if ($isNew && $discoveredBy !== DiscoveredBy::Backfill) {
                CartAbandoned::dispatch($cart->tenant_id, $cart->id);
            }

            return $isNew;
        });
    }

    /**
     * @return bool true when the cart was known and is now marked purchased
     */
    public function markCartPurchased(string $externalId): bool
    {
        $cart = Cart::query()->where('external_id', $externalId)->first();

        if ($cart === null || $cart->status === CartStatus::Purchased) {
            return false;
        }

        $cart->update(['status' => CartStatus::Purchased, 'purchased_at' => now()]);

        CartPurchased::dispatch($cart->tenant_id, $cart->id);

        return true;
    }

    /**
     * @return bool true when the order is new
     */
    public function recordOrder(OrderData $data): bool
    {
        return DB::transaction(function () use ($data) {
            $customer = $this->customer($data->customer);

            $order = Order::query()->firstOrNew(['external_id' => $data->externalId]);
            $isNew = ! $order->exists;
            $wasCancelled = $order->cancelled_at !== null;
            $previousRefund = $order->refunded_halalas ?? 0;
            $keepBreakdown = ! $isNew && ! $data->breakdownKnown;

            $order->fill([
                'customer_id' => $customer->id ?? $order->customer_id,
                'phone_hash' => $customer->phone_hash ?? $order->phone_hash,
                'total_halalas' => $data->totalHalalas,
                'discount_halalas' => $keepBreakdown ? $order->discount_halalas : $data->discountHalalas,
                'shipping_halalas' => $keepBreakdown ? $order->shipping_halalas : $data->shippingHalalas,
                'refunded_halalas' => max($previousRefund, $data->refundedHalalas),
                'currency' => $data->currency,
                'status' => $data->status,
                'payment_method' => $data->paymentMethod,
                'coupon_code' => $data->couponCode,
                'placed_at' => $order->placed_at ?? $data->placedAt,
                'cancelled_at' => $data->cancelled ? ($order->cancelled_at ?? now()) : $order->cancelled_at,
            ])->save();

            if ($isNew) {
                $order->items()->createMany(array_map(fn (LineItemData $item) => [
                    'product_external_id' => $item->productExternalId,
                    'category_external_id' => $item->categoryExternalId ?? $this->categoryOf($item->productExternalId),
                    'quantity' => $item->quantity,
                    'unit_price_halalas' => $item->unitPriceHalalas,
                ], $data->items));

                OrderPlaced::dispatch($order->tenant_id, $order->id);
            }

            if (! $wasCancelled && $order->cancelled_at !== null) {
                OrderCancelled::dispatch($order->tenant_id, $order->id);
            }

            if ($order->refunded_halalas > $previousRefund) {
                OrderRefunded::dispatch($order->tenant_id, $order->id);
            }

            return $isNew;
        });
    }

    public function recordProduct(ProductData $data): void
    {
        Product::query()->updateOrCreate(['external_id' => $data->externalId], [
            'name' => $data->name,
            'sku' => $data->sku,
            'category_external_id' => $data->categoryExternalId,
            'price_halalas' => $data->priceHalalas,
            'regular_price_halalas' => $data->regularPriceHalalas,
            'quantity' => $data->quantity,
            'status' => $data->status,
            'description' => $data->description,
            'url' => $data->url,
            'is_available' => $data->isAvailable,
            'unlimited_quantity' => $data->unlimitedQuantity,
            'variants' => array_map(fn ($variant) => $variant->toArray(), $data->variants),
            'synced_at' => now(),
        ]);

        ProductChanged::dispatch($this->tenants->require(), $data->externalId);
    }

    /** `product.deleted`, or gone from a full catalog sync. */
    public function removeProduct(string $externalId): void
    {
        $updated = Product::query()->where('external_id', $externalId)->update(['status' => 'deleted', 'synced_at' => now()]);

        if ($updated > 0) {
            ProductChanged::dispatch($this->tenants->require(), $externalId);
        }
    }

    private function customer(?CustomerData $data): ?Customer
    {
        if ($data === null || ($data->externalId === null && $data->phone === null)) {
            return null;
        }

        $hash = $data->phone === null ? null : $this->hasher->hash($this->tenants->require(), $data->phone);

        $customer = $data->externalId !== null
            ? Customer::query()->firstOrNew(['external_id' => $data->externalId])
            : Customer::query()->firstOrNew(['phone_hash' => $hash, 'external_id' => null]);

        if ($data->phone !== null) {
            $customer->phone = $data->phone->e164;
            $customer->phone_hash = $hash;
        }

        $customer->save();

        return $customer;
    }

    private function categoryOf(?string $productExternalId): ?string
    {
        if ($productExternalId === null) {
            return null;
        }

        return Product::query()->where('external_id', $productExternalId)->value('category_external_id');
    }
}
