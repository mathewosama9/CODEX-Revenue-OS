<?php

namespace App\Modules\Commerce;

use App\Modules\Commerce\Data\CartSnapshot;
use App\Modules\Commerce\Data\LineItemData;
use App\Modules\Commerce\Data\OrderSnapshot;
use App\Modules\Commerce\Models\Cart;
use App\Modules\Commerce\Models\CartItem;
use App\Modules\Commerce\Models\Category;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\OrderItem;
use App\Modules\Commerce\Models\StoreConnection;
use App\Shared\Phone\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Public read API of M02 for other modules (09 §9.4.2). Returns snapshots,
 * never models. Runs inside the tenant context.
 */
class Commerce
{
    public function __construct(private readonly CommerceAdapter $adapter) {}

    public function cart(int $cartId): ?CartSnapshot
    {
        $cart = Cart::query()->with(['items', 'customer'])->find($cartId);

        return $cart === null ? null : $this->cartSnapshot($cart);
    }

    /**
     * Ask the platform, at send time, whether the cart is still abandoned
     * (S2 check #10). Fails safe: if we cannot tell, treat it as not abandoned.
     */
    public function isStillAbandoned(int $cartId): bool
    {
        $cart = Cart::query()->find($cartId);
        $store = $this->activeStore();

        if ($cart === null || $cart->status !== CartStatus::Abandoned || $store === null) {
            return false;
        }

        try {
            $live = $this->adapter->abandonedCart($store, $cart->external_id);
        } catch (Throwable $exception) {
            Log::warning('Live cart check failed; not sending.', ['cart_id' => $cartId, 'error' => $exception::class]);

            return false;
        }

        if ($live === null || $live->purchased) {
            app(CommerceRecorder::class)->markCartPurchased($cart->external_id);

            return false;
        }

        return true;
    }

    public function order(int $orderId): ?OrderSnapshot
    {
        $order = Order::query()->with('items')->find($orderId);

        return $order === null ? null : $this->orderSnapshot($order);
    }

    /**
     * @return list<OrderSnapshot>
     */
    public function ordersForSubject(string $phoneHash, CarbonImmutable $from, CarbonImmutable $to): array
    {
        return array_values(Order::query()
            ->with('items')
            ->where('phone_hash', $phoneHash)
            ->whereBetween('placed_at', [$from, $to])
            ->orderBy('placed_at')
            ->get()
            ->map(fn (Order $order) => $this->orderSnapshot($order))
            ->all());
    }

    public function hasOrderedBefore(string $phoneHash, CarbonImmutable $before): bool
    {
        return Order::query()
            ->where('phone_hash', $phoneHash)
            ->where('placed_at', '<', $before)
            ->whereNull('cancelled_at')
            ->exists();
    }

    /**
     * @return list<array{external_id: string, name: string}>
     */
    public function categories(): array
    {
        return array_values(Category::query()->orderBy('name')->get()
            ->map(fn (Category $category) => ['external_id' => $category->external_id, 'name' => $category->name])
            ->all());
    }

    public function isStoreActive(): bool
    {
        return $this->activeStore() !== null;
    }

    private function activeStore(): ?StoreConnection
    {
        return StoreConnection::query()->where('status', StoreStatus::Active)->first();
    }

    private function cartSnapshot(Cart $cart): CartSnapshot
    {
        return new CartSnapshot(
            id: $cart->id,
            tenantId: $cart->tenant_id,
            externalId: $cart->external_id,
            phoneHash: $cart->phone_hash,
            phone: PhoneNumber::tryParse($cart->customer?->phone),
            totalHalalas: $cart->total_halalas,
            currency: $cart->currency,
            checkoutUrl: $cart->checkout_url,
            status: $cart->status,
            abandonedAt: $cart->abandoned_at,
            items: array_values($cart->items->map(fn (CartItem $item) => new LineItemData(
                $item->product_external_id,
                $item->name,
                $item->quantity,
                $item->unit_price_halalas,
            ))->all()),
        );
    }

    private function orderSnapshot(Order $order): OrderSnapshot
    {
        return new OrderSnapshot(
            id: $order->id,
            tenantId: $order->tenant_id,
            phoneHash: $order->phone_hash,
            totalHalalas: $order->total_halalas,
            discountHalalas: $order->discount_halalas,
            shippingHalalas: $order->shipping_halalas,
            refundedHalalas: $order->refunded_halalas,
            couponCode: $order->coupon_code,
            placedAt: $order->placed_at,
            cancelled: $order->cancelled_at !== null,
            items: array_values($order->items->map(fn (OrderItem $item) => new LineItemData(
                $item->product_external_id,
                null,
                $item->quantity,
                $item->unit_price_halalas,
                $item->category_external_id,
            ))->all()),
        );
    }
}
