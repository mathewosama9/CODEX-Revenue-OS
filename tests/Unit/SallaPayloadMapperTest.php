<?php

use App\Modules\Commerce\Salla\SallaPayloadMapper;
use App\Modules\Commerce\Salla\SallaTokens;
use App\Shared\Money\Currencies;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

// Booted for config/currencies.php; no database.
uses(TestCase::class);

it('reads the checkout link under both field names', function () {
    $mapper = new SallaPayloadMapper;

    expect($mapper->cart(['id' => 1, 'urls' => ['checkout' => 'https://new']])->checkoutUrl)->toBe('https://new')
        ->and($mapper->cart(['id' => 1, 'checkout_url' => 'https://old'])->checkoutUrl)->toBe('https://old');
});

it('reads amounts as numbers, strings or amount objects, in halalas', function () {
    $mapper = new SallaPayloadMapper;

    expect($mapper->cart(['id' => 1, 'total' => 249.5])->totalHalalas)->toBe(24950)
        ->and($mapper->cart(['id' => 1, 'total' => '249.50'])->totalHalalas)->toBe(24950)
        ->and($mapper->cart(['id' => 1, 'total' => ['amount' => 249.5, 'currency' => 'SAR']])->totalHalalas)->toBe(24950);
});

it('joins a split mobile into E.164', function () {
    $customer = (new SallaPayloadMapper)->customer(['id' => 9, 'mobile' => '512345678', 'mobile_code' => '+966']);

    expect($customer?->phone?->e164)->toBe('+966512345678')
        ->and($customer?->externalId)->toBe('9');
});

it('keeps a customer without a valid phone but drops the phone', function () {
    $customer = (new SallaPayloadMapper)->customer(['id' => 9, 'mobile' => '123']);

    expect($customer?->phone)->toBeNull();
});

it('reads Salla date objects in Riyadh time as UTC', function () {
    $cart = (new SallaPayloadMapper)->cart([
        'id' => 1,
        'created_at' => ['date' => '2026-10-25 15:00:00.000000', 'timezone' => 'Asia/Riyadh'],
    ]);

    expect($cart->abandonedAt?->toIso8601String())->toBe('2026-10-25T12:00:00+00:00');
});

it('maps an order with discounts, shipping and cancellation', function () {
    $order = (new SallaPayloadMapper)->order([
        'id' => 77,
        'status' => ['slug' => 'canceled'],
        'payment_method' => 'cod',
        'amounts' => [
            'total' => ['amount' => 300, 'currency' => 'SAR'],
            'shipping_cost' => ['amount' => 25],
            'discounts' => [['discount' => 30]],
        ],
        'items' => [['product' => ['id' => 5], 'quantity' => 2, 'amounts' => ['price' => ['amount' => 150]]]],
        'date' => ['date' => '2026-10-25 10:00:00', 'timezone' => 'Asia/Riyadh'],
    ]);

    expect($order->totalHalalas)->toBe(30000)
        ->and($order->discountHalalas)->toBe(3000)
        ->and($order->shippingHalalas)->toBe(2500)
        ->and($order->cancelled)->toBeTrue()
        ->and($order->items[0]->productExternalId)->toBe('5')
        ->and($order->items[0]->unitPriceHalalas)->toBe(15000);
});

it('reads token expiry as a timestamp or as seconds', function () {
    CarbonImmutable::setTestNow('2026-10-25 12:00:00');

    $fromSeconds = SallaTokens::fromResponse(['access_token' => 'a', 'refresh_token' => 'r', 'expires_in' => 3600]);
    $fromTimestamp = SallaTokens::fromResponse(['access_token' => 'a', 'refresh_token' => 'r', 'expires' => CarbonImmutable::now()->addDays(14)->timestamp]);

    expect($fromSeconds->expiresAt->toDateTimeString())->toBe('2026-10-25 13:00:00')
        ->and($fromTimestamp->expiresAt->toDateTimeString())->toBe('2026-11-08 12:00:00');

    CarbonImmutable::setTestNow();
});

it('refuses a token payload without tokens', function () {
    SallaTokens::fromResponse(['expires_in' => 10]);
})->throws(InvalidArgumentException::class);

it('reads amounts in the workspace currency, three decimals for KWD', function () {
    $mapper = (new SallaPayloadMapper)->withCurrency(Currencies::get('KWD'));

    $order = $mapper->order(['id' => 1, 'amounts' => ['total' => ['amount' => 12.345, 'currency' => 'KWD'], 'shipping_cost' => '1.5']]);

    expect($order->totalHalalas)->toBe(12345)
        ->and($order->shippingHalalas)->toBe(1500)
        ->and($order->currency)->toBe('KWD')
        ->and((new SallaPayloadMapper)->withCurrency(Currencies::get('EGP'))->cart(['id' => 1, 'total' => '345.50'])->totalHalalas)->toBe(34550);
});

it('logs an amount in another currency and still stores it as the workspace currency', function () {
    Log::spy();

    $cart = (new SallaPayloadMapper)->withCurrency(Currencies::get('SAR'))->cart(['id' => 1, 'total' => ['amount' => 100, 'currency' => 'USD']]);

    expect($cart->currency)->toBe('SAR')
        ->and($cart->totalHalalas)->toBe(10000);
    Log::shouldHaveReceived('warning')->once();
});
