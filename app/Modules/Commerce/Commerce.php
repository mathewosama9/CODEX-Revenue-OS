<?php

namespace App\Modules\Commerce;

use App\Modules\Commerce\Data\CartSnapshot;
use App\Modules\Commerce\Data\LineItemData;
use App\Modules\Commerce\Data\OrderSnapshot;
use App\Modules\Commerce\Models\Cart;
use App\Modules\Commerce\Models\CartItem;
use App\Modules\Commerce\Models\Category;
use App\Modules\Commerce\Models\Customer;
use App\Modules\Commerce\Models\Order;
use App\Modules\Commerce\Models\OrderItem;
use App\Modules\Commerce\Models\Product;
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
     * (S2 check #10). Only a confirmed purchase marks the cart purchased; an
     * unreachable platform is "unknown", so the caller retries instead of
     * giving up on the customer (P1/P2 review B4).
     */
    public function liveCartStatus(int $cartId): LiveCartStatus
    {
        $cart = Cart::query()->find($cartId);
        $store = $this->activeStore();

        if ($cart === null) {
            return LiveCartStatus::Gone;
        }

        if ($cart->status !== CartStatus::Abandoned) {
            return LiveCartStatus::Purchased;
        }

        if ($store === null) {
            return LiveCartStatus::Unknown;
        }

        try {
            $live = $this->adapter->abandonedCart($store, $cart->external_id);
        } catch (Throwable $exception) {
            Log::warning('Live cart check failed; will retry.', ['cart_id' => $cartId, 'error' => $exception::class]);

            return LiveCartStatus::Unknown;
        }

        if ($live === null) {
            return LiveCartStatus::Gone;
        }

        if ($live->purchased) {
            app(CommerceRecorder::class)->markCartPurchased($cart->external_id);

            return LiveCartStatus::Purchased;
        }

        return LiveCartStatus::Abandoned;
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

    /**
     * Orders of many customers in one pass (the profit report reads thousands).
     *
     * @param  list<string>  $phoneHashes
     * @return array<string, list<OrderSnapshot>>
     */
    public function ordersForSubjects(array $phoneHashes, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $result = [];

        foreach (array_chunk($phoneHashes, 1000) as $chunk) {
            $orders = Order::query()
                ->with('items')
                ->whereIn('phone_hash', $chunk)
                ->whereBetween('placed_at', [$from, $to])
                ->orderBy('placed_at')
                ->get();

            foreach ($orders as $order) {
                $result[(string) $order->phone_hash][] = $this->orderSnapshot($order);
            }
        }

        return $result;
    }

    /**
     * "+9665•••••678" — enough for staff to find the chat in their WhatsApp app.
     */
    public function maskedPhone(string $phoneHash): ?string
    {
        $phone = Customer::query()->where('phone_hash', $phoneHash)->whereNotNull('phone')->value('phone');

        return is_string($phone) ? substr($phone, 0, 5).str_repeat('•', max(0, strlen($phone) - 8)).substr($phone, -3) : null;
    }

    /**
     * Data-subject export (PDPL): what we hold about this customer.
     *
     * @return array<string, mixed>
     */
    public function exportSubject(string $phoneHash): array
    {
        return [
            'customers' => Customer::query()->where('phone_hash', $phoneHash)->get(['external_id', 'phone', 'created_at'])
                ->map(fn (Customer $customer) => ['external_id' => $customer->external_id, 'phone' => $customer->phone])->all(),
            'carts' => Cart::query()->where('phone_hash', $phoneHash)->get(['external_id', 'total_halalas', 'status', 'abandoned_at'])->toArray(),
            'orders' => Order::query()->where('phone_hash', $phoneHash)->get(['external_id', 'total_halalas', 'status', 'placed_at'])->toArray(),
        ];
    }

    /**
     * Data-subject deletion: drop the phone; orders stay for the merchant's
     * accounting, linked only by an unreadable hash.
     */
    public function forgetSubject(string $phoneHash): int
    {
        Cart::query()->where('phone_hash', $phoneHash)->update(['checkout_url' => null]);

        return Customer::query()->where('phone_hash', $phoneHash)->update(['phone' => null]);
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
        $categories = Product::query()
            ->whereIn('external_id', $cart->items->pluck('product_external_id')->filter()->all())
            ->pluck('category_external_id', 'external_id');

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
                $item->product_external_id === null ? null : $categories->get($item->product_external_id),
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
