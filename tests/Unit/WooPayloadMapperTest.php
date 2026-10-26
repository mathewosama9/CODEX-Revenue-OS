<?php

use App\Modules\Commerce\WooCommerce\WooPayloadMapper;
use App\Modules\Commerce\WooCommerce\WooSignature;
use App\Shared\Money\Currencies;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

// Booted for config/currencies.php; no database.
uses(TestCase::class);

function wooCartPayload(array $overrides = []): array
{
    return $overrides + [
        'id' => 'c_01jbx8',
        'status' => 'open',
        'currency' => 'SAR',
        'total' => '199.50',
        'items' => [['product_id' => '123', 'variation_id' => '456', 'name' => 'عود كمبودي - 50ml', 'quantity' => 2, 'unit_price' => '99.75', 'category_id' => '15']],
        'phone' => '+966551234567',
        'consent' => true,
        'consent_text' => 'أوافق على استلام رسائل واتساب.',
        'consent_text_version' => 'a1b2c3d4',
        'recovery_url' => 'https://shop.example.com/?codex_cart=tok',
        'customer_id' => '12',
        'updated_at' => '2026-10-28T10:15:00Z',
        'abandoned_at' => '2026-10-28T10:10:00Z',
    ];
}

it('maps a plugin cart to halalas, items and the recovery link', function () {
    $cart = (new WooPayloadMapper)->cart(wooCartPayload());

    expect($cart->externalId)->toBe('c_01jbx8')
        ->and($cart->totalHalalas)->toBe(19950)
        ->and($cart->currency)->toBe('SAR')
        ->and($cart->checkoutUrl)->toBe('https://shop.example.com/?codex_cart=tok')
        ->and($cart->customer?->externalId)->toBe('12')
        ->and($cart->customer?->phone?->e164)->toBe('+966551234567')
        ->and($cart->abandonedAt?->toIso8601String())->toBe('2026-10-28T10:10:00+00:00')
        ->and($cart->purchased)->toBeFalse()
        ->and($cart->items[0]->productExternalId)->toBe('123')
        ->and($cart->items[0]->quantity)->toBe(2)
        ->and($cart->items[0]->unitPriceHalalas)->toBe(9975)
        ->and($cart->items[0]->categoryExternalId)->toBe('15');
});

it('drops the phone of a cart without consent, and reads purchased carts', function () {
    $mapper = new WooPayloadMapper;

    expect($mapper->cart(wooCartPayload(['consent' => false]))->customer?->phone)->toBeNull()
        ->and($mapper->cart(wooCartPayload(['status' => 'purchased']))->purchased)->toBeTrue()
        ->and($mapper->cart(wooCartPayload(['recovery_url' => 'javascript:alert(1)']))->checkoutUrl)->toBeNull();
});

it('maps a REST v3 order with coupon, refunds and the billing phone', function () {
    $order = (new WooPayloadMapper)->order([
        'id' => 789,
        'status' => 'processing',
        'currency' => 'SAR',
        'total' => '230.00',
        'discount_total' => '20.00',
        'shipping_total' => '25.00',
        'payment_method' => 'cod',
        'customer_id' => 0,
        'billing' => ['phone' => '0551234567'],
        'coupon_lines' => [['code' => 'eid10']],
        'date_created_gmt' => '2026-10-28T10:15:00',
        'line_items' => [['product_id' => 123, 'name' => 'عود', 'quantity' => 1, 'price' => 205]],
        'refunds' => [['id' => 1, 'total' => '-30.00']],
    ]);

    expect($order->externalId)->toBe('789')
        ->and($order->totalHalalas)->toBe(23000)
        ->and($order->discountHalalas)->toBe(2000)
        ->and($order->shippingHalalas)->toBe(2500)
        ->and($order->couponCode)->toBe('eid10')
        ->and($order->customer?->externalId)->toBeNull()
        ->and($order->customer?->phone?->e164)->toBe('+966551234567')
        ->and($order->placedAt->toIso8601String())->toBe('2026-10-28T10:15:00+00:00')
        ->and($order->items[0]->unitPriceHalalas)->toBe(20500)
        ->and($order->refundedHalalas)->toBe(3000)
        ->and($order->cancelled)->toBeFalse();

    expect((new WooPayloadMapper)->order(['id' => 1, 'status' => 'cancelled'])->cancelled)->toBeTrue();
});

it('maps a product on sale with managed stock', function () {
    $product = (new WooPayloadMapper)->product([
        'id' => 123,
        'name' => 'عود &amp; مسك',
        'sku' => 'OUD-1',
        'status' => 'publish',
        'price' => '149.00',
        'regular_price' => '199.00',
        'sale_price' => '149.00',
        'manage_stock' => true,
        'stock_quantity' => 4,
        'stock_status' => 'instock',
        'permalink' => 'https://shop.example.com/p/oud',
        'description' => '<p>عود كمبودي فاخر</p>',
        'categories' => [['id' => 15, 'name' => 'عود']],
    ]);

    expect($product->name)->toBe('عود & مسك')
        ->and($product->priceHalalas)->toBe(14900)
        ->and($product->regularPriceHalalas)->toBe(19900)
        ->and($product->quantity)->toBe(4)
        ->and($product->unlimitedQuantity)->toBeFalse()
        ->and($product->isAvailable)->toBeTrue()
        ->and($product->status)->toBe('available')
        ->and($product->categoryExternalId)->toBe('15')
        ->and($product->description)->toBe('عود كمبودي فاخر')
        ->and($product->url)->toBe('https://shop.example.com/p/oud');
});

it('reads unmanaged stock, out of stock and unpublished products', function () {
    $mapper = new WooPayloadMapper;
    $base = ['id' => 1, 'name' => 'x', 'status' => 'publish', 'price' => '10', 'regular_price' => '10', 'sale_price' => ''];

    $unmanaged = $mapper->product($base + ['manage_stock' => false, 'stock_quantity' => null, 'stock_status' => 'instock']);
    $out = $mapper->product($base + ['manage_stock' => false, 'stock_status' => 'outofstock']);
    $draft = $mapper->product(['status' => 'draft'] + $base);

    expect($unmanaged->quantity)->toBeNull()
        ->and($unmanaged->unlimitedQuantity)->toBeTrue()
        ->and($unmanaged->priceHalalas)->toBe(1000)
        ->and($out->status)->toBe('out')
        ->and($out->isAvailable)->toBeFalse()
        ->and($draft->status)->toBe('hidden');
});

it('maps variations to variants named by their options', function () {
    $product = (new WooPayloadMapper)->product(['id' => 1, 'name' => 'عود', 'type' => 'variable', 'status' => 'publish'], [
        ['id' => 11, 'attributes' => [['name' => 'Size', 'option' => '50ml']], 'price' => '99', 'regular_price' => '120', 'sale_price' => '99', 'manage_stock' => true, 'stock_quantity' => 3, 'stock_status' => 'instock'],
        ['id' => 12, 'attributes' => [['name' => 'Size', 'option' => '100ml']], 'price' => '180', 'regular_price' => '180', 'sale_price' => '', 'manage_stock' => false, 'stock_status' => 'outofstock'],
    ]);

    expect($product->variants)->toHaveCount(2)
        ->and($product->variants[0]->toArray())->toBe(['external_id' => '11', 'name' => '50ml', 'price_halalas' => 9900, 'quantity' => 3, 'unlimited_quantity' => false])
        ->and($product->variants[1]->toArray())->toBe(['external_id' => '12', 'name' => '100ml', 'price_halalas' => 18000, 'quantity' => 0, 'unlimited_quantity' => false]);
});

it('signs and verifies like the protocol says', function () {
    $body = '{"event":"connector.ping"}';
    $signature = WooSignature::forBody('secret', 1_800_000_000, $body);

    expect($signature)->toBe('sha256='.hash_hmac('sha256', "1800000000.{$body}", 'secret'))
        ->and(WooSignature::verifyBody('secret', '1800000000', $signature, $body, 1_800_000_100))->toBeTrue()
        ->and(WooSignature::verifyBody('secret', '1800000000', $signature, $body, 1_800_000_301))->toBeFalse()
        ->and(WooSignature::verifyBody('other', '1800000000', $signature, $body, 1_800_000_000))->toBeFalse()
        ->and(WooSignature::verifyBody('', '1800000000', $signature, $body, 1_800_000_000))->toBeFalse()
        ->and(WooSignature::forGet('secret', 5, '/codex/v1/carts', ['per_page' => 100, 'since' => '2026-10-28T10:00:00Z', 'page' => 1]))
        ->toBe('sha256='.hash_hmac('sha256', '5.GET./codex/v1/carts?page=1&per_page=100&since=2026-10-28T10%3A00%3A00Z', 'secret'))
        ->and(WooSignature::forGet('secret', 5, '/codex/v1/ping'))->toBe('sha256='.hash_hmac('sha256', '5.GET./codex/v1/ping?', 'secret'));
});

it('reads Egyptian and Kuwaiti amounts exactly in their minor units', function () {
    $egp = (new WooPayloadMapper)->withCurrency(Currencies::get('EGP'));
    $kwd = (new WooPayloadMapper)->withCurrency(Currencies::get('KWD'));

    $cart = $egp->cart(wooCartPayload(['currency' => 'EGP', 'total' => '345.50', 'items' => [['product_id' => '1', 'quantity' => 1, 'unit_price' => '345.50']]]));
    expect($cart->totalHalalas)->toBe(34550)
        ->and($cart->currency)->toBe('EGP')
        ->and($cart->items[0]->unitPriceHalalas)->toBe(34550);

    $order = $kwd->order(['id' => 1, 'currency' => 'KWD', 'total' => '12.345', 'discount_total' => '0.005', 'shipping_total' => '1.250', 'line_items' => [['product_id' => 5, 'quantity' => 1, 'price' => 11.1]], 'refunds' => [['total' => '-2.001']]]);
    expect($order->totalHalalas)->toBe(12345)
        ->and($order->discountHalalas)->toBe(5)
        ->and($order->shippingHalalas)->toBe(1250)
        ->and($order->items[0]->unitPriceHalalas)->toBe(11100)
        ->and($order->refundedHalalas)->toBe(2001)
        ->and($order->currency)->toBe('KWD');

    expect($kwd->product(['id' => 5, 'status' => 'publish', 'price' => '12.345', 'regular_price' => '15.000', 'sale_price' => '12.345'])->priceHalalas)->toBe(12345);
});

it('tells a record in another currency than the workspace, so callers skip it', function () {
    Log::spy();
    $mapper = (new WooPayloadMapper)->withCurrency(Currencies::get('EGP'));

    expect($mapper->inWorkspaceCurrency(['id' => 'c1', 'currency' => 'EGP'], 'cart'))->toBeTrue()
        ->and($mapper->inWorkspaceCurrency(['id' => 'c1', 'currency' => 'egp'], 'cart'))->toBeTrue()
        ->and($mapper->inWorkspaceCurrency(['id' => 'c1'], 'cart'))->toBeTrue()
        ->and($mapper->inWorkspaceCurrency(['id' => 'c2', 'currency' => 'USD'], 'order'))->toBeFalse();

    Log::shouldHaveReceived('warning')->once();
});

it('reads an order phone typed locally in the billing country', function () {
    $order = (new WooPayloadMapper)->withCurrency(Currencies::get('EGP'))->order([
        'id' => 9, 'currency' => 'EGP', 'total' => '345',
        'billing' => ['phone' => '010 1234 5678', 'country' => 'EG'],
    ]);

    expect($order->customer?->phone?->e164)->toBe('+201012345678');
});

it('drops an amount beyond a bigint instead of storing a saturated one', function () {
    Log::spy();
    $iqd = (new WooPayloadMapper)->withCurrency(Currencies::get('IQD'));

    expect($iqd->product(['id' => 5, 'status' => 'publish', 'price' => '99999999999999999999', 'regular_price' => '25000', 'sale_price' => ''])->priceHalalas)->toBeNull()
        ->and($iqd->cart(wooCartPayload(['currency' => 'IQD', 'total' => '1e30']))->totalHalalas)->toBe(0);
});
