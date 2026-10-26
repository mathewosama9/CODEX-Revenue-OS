<?php

use App\Models\Team;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Modules\Compliance\DataSubjectRequests;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Webhooks\WebhookEvent;
use App\Shared\Webhooks\WebhookStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Http::fake(fn () => Http::response('stray request', 500));
});

/**
 * @return array{tenant: int, store: string, secret: string}
 */
function whStore(string $secret = 'connector-secret-a'): array
{
    $tenant = Team::create(['name' => 'متجر', 'is_personal' => false])->id;
    $storeId = 'woo_'.strtolower((string) Str::ulid());

    app(TenantContext::class)->run($tenant, fn () => StoreConnection::query()->create([
        'provider' => 'woocommerce',
        'external_store_id' => $storeId,
        'site_url' => 'https://shop.example.com',
        'credentials' => ['consumer_key' => 'ck_x', 'consumer_secret' => 'cs_x', 'connector_secret' => $secret],
        'status' => 'active',
    ]));
    TenantRoute::bind(TenantRoute::WOOCOMMERCE, $storeId, $tenant);

    return ['tenant' => $tenant, 'store' => $storeId, 'secret' => $secret];
}

function whPost(string $storeId, string $event, array $data, string $secret, ?int $timestamp = null, ?string $eventId = null, ?string $signature = null)
{
    $body = json_encode(['event' => $event, 'occurred_at' => now()->toIso8601String(), 'plugin_version' => '1.0.0', 'data' => $data]);
    $timestamp ??= now()->getTimestamp();

    return test()->call('POST', "/webhooks/woocommerce/{$storeId}", server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CODEX_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_CODEX_SIGNATURE' => $signature ?? 'sha256='.hash_hmac('sha256', "{$timestamp}.{$body}", $secret),
        'HTTP_X_CODEX_EVENT_ID' => $eventId ?? (string) Str::uuid(),
    ], content: $body);
}

function whCart(array $overrides = []): array
{
    return $overrides + [
        'id' => 'c_01jbx8',
        'status' => 'open',
        'currency' => 'SAR',
        'total' => '199.00',
        'items' => [['product_id' => '123', 'name' => 'عود', 'quantity' => 1, 'unit_price' => '199.00']],
        'phone' => '+966551234567',
        'consent' => true,
        'consent_text' => 'أوافق على استلام رسائل واتساب من المتجر عن طلبي والعروض.',
        'consent_text_version' => 'a1b2c3d4',
        'recovery_url' => 'https://shop.example.com/?codex_cart=tok',
        'customer_id' => null,
        'updated_at' => '2026-10-28T10:15:00Z',
        'abandoned_at' => '2026-10-28T10:15:00Z',
    ];
}

it('accepts a correctly signed event, stores it once and processes it', function () {
    $store = whStore();

    whPost($store['store'], 'connector.ping', ['woocommerce_version' => '9.3', 'checkout' => 'classic', 'currency' => 'SAR'], $store['secret'])->assertOk();

    $event = WebhookEvent::sole();

    expect($event->provider)->toBe('woocommerce')
        ->and($event->event_type)->toBe('connector.ping')
        ->and($event->status)->toBe(WebhookStatus::Processed)
        ->and($event->tenant_id)->toBe($store['tenant'])
        ->and($event->external_event_id)->toStartWith($store['store'].':')
        ->and(DB::table('store_connections')->value('last_webhook_at'))->not->toBeNull();
});

it('rejects a wrong signature without storing anything', function () {
    $store = whStore();

    whPost($store['store'], 'connector.ping', [], $store['secret'], signature: 'sha256=nope')->assertUnauthorized();
    whPost($store['store'], 'connector.ping', [], 'another-secret')->assertUnauthorized();

    expect(WebhookEvent::count())->toBe(0);
});

it('rejects a timestamp outside the five-minute window', function () {
    $store = whStore();

    whPost($store['store'], 'connector.ping', [], $store['secret'], timestamp: now()->getTimestamp() - 301)->assertUnauthorized();
    whPost($store['store'], 'connector.ping', [], $store['secret'], timestamp: now()->getTimestamp() + 301)->assertUnauthorized();
    whPost($store['store'], 'connector.ping', [], $store['secret'], timestamp: now()->getTimestamp() - 200)->assertOk();

    expect(WebhookEvent::count())->toBe(1);
});

it('treats a repeated event id as one event and still answers 200', function () {
    $store = whStore();
    $id = (string) Str::uuid();

    whPost($store['store'], 'cart.updated', whCart(), $store['secret'], eventId: $id)->assertOk();
    whPost($store['store'], 'cart.updated', whCart(), $store['secret'], eventId: $id)->assertOk();

    expect(WebhookEvent::count())->toBe(1);
});

it('answers 404 for an unknown or disconnected store', function () {
    $store = whStore();

    whPost('woo_'.strtolower((string) Str::ulid()), 'connector.ping', [], $store['secret'])->assertNotFound();
    $this->postJson('/webhooks/woocommerce/not-a-store-id', [])->assertNotFound();

    DB::table('store_connections')->update(['status' => 'uninstalled', 'credentials' => null]);
    whPost($store['store'], 'connector.ping', [], $store['secret'])->assertNotFound();

    expect(WebhookEvent::count())->toBe(0);
});

it('refuses bodies that are too large or malformed', function () {
    $store = whStore();
    $timestamp = now()->getTimestamp();
    $big = str_repeat('x', 1024 * 1024 + 1);

    $this->call('POST', "/webhooks/woocommerce/{$store['store']}", server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CODEX_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_CODEX_SIGNATURE' => 'sha256='.hash_hmac('sha256', "{$timestamp}.{$big}", $store['secret']),
        'HTTP_X_CODEX_EVENT_ID' => (string) Str::uuid(),
    ], content: $big)->assertStatus(413);

    $this->call('POST', "/webhooks/woocommerce/{$store['store']}", server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CODEX_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_CODEX_SIGNATURE' => 'sha256='.hash_hmac('sha256', "{$timestamp}.not json", $store['secret']),
        'HTTP_X_CODEX_EVENT_ID' => (string) Str::uuid(),
    ], content: 'not json')->assertStatus(422);

    expect(WebhookEvent::count())->toBe(0);
});

it('keeps one store away from another: its secret signs only its own URL', function () {
    $a = whStore('secret-a');
    $b = whStore('secret-b');

    whPost($b['store'], 'cart.updated', whCart(), $a['secret'])->assertUnauthorized();
    whPost($a['store'], 'cart.updated', whCart(), $a['secret'])->assertOk();

    expect(DB::table('carts')->where('tenant_id', $a['tenant'])->count())->toBe(1)
        ->and(DB::table('carts')->where('tenant_id', $b['tenant'])->count())->toBe(0);
});

it('grants WhatsApp consent from a ticked checkout box with a phone, before recording the cart', function () {
    $store = whStore();

    whPost($store['store'], 'cart.updated', whCart(), $store['secret'])->assertOk();

    $consent = DB::table('consent_records')->sole();
    $evidence = json_decode($consent->evidence, true);

    expect($consent->tenant_id)->toBe($store['tenant'])
        ->and($consent->status)->toBe('granted')
        ->and($consent->source)->toBe('checkout_opt_in')
        ->and($evidence['text_version'])->toBe('a1b2c3d4')
        ->and($evidence['text'])->toContain('أوافق')
        ->and(json_encode($evidence))->not->toContain('551234567')
        ->and(DB::table('carts')->value('phone_hash'))->toHaveLength(64)
        ->and(DB::table('carts')->value('total_halalas'))->toBe(19900);

    // The same cart again: no second grant.
    whPost($store['store'], 'cart.updated', whCart(['total' => '250.00']), $store['secret'])->assertOk();
    expect(DB::table('consent_records')->count())->toBe(1);
});

it('never re-grants consent after a WhatsApp opt-out from the same checkout', function () {
    $store = whStore();

    whPost($store['store'], 'cart.updated', whCart(), $store['secret'])->assertOk();

    $phone = PhoneNumber::tryParse('+966551234567');
    app(TenantContext::class)->run($store['tenant'], fn () => app(ConsentGate::class)->revoke($phone, ConsentSource::OptOutKeyword));

    // The customer comes back to the same cart (box still ticked in the session) and edits it.
    whPost($store['store'], 'cart.updated', whCart(['total' => '250.00', 'updated_at' => now()->addMinute()->toIso8601String()]), $store['secret'])->assertOk();

    expect(app(TenantContext::class)->run($store['tenant'], fn () => app(ConsentGate::class)->mayMessage($phone)))->toBeFalse()
        ->and(DB::table('consent_records')->pluck('status')->all())->toBe(['granted', 'revoked']);
});

it('lets a data deletion request erase a WooCommerce customer and their raw cart events', function () {
    $store = whStore();
    whPost($store['store'], 'cart.updated', whCart(), $store['secret'])->assertOk();

    $phone = PhoneNumber::tryParse('0551234567');
    $result = app(TenantContext::class)->run($store['tenant'], fn () => app(DataSubjectRequests::class)->delete($phone, null));

    expect($result['webhook_payloads'])->toBe(1)
        ->and(json_encode(WebhookEvent::sole()->payload))->not->toContain('551234567')
        ->and(DB::table('customers')->whereNotNull('phone')->count())->toBe(0)
        ->and(app(TenantContext::class)->run($store['tenant'], fn () => app(ConsentGate::class)->mayMessage($phone)))->toBeFalse();
});

it('grants nothing without consent or without a phone', function () {
    $store = whStore();

    whPost($store['store'], 'cart.updated', whCart(['id' => 'c_1', 'consent' => false]), $store['secret'])->assertOk();
    whPost($store['store'], 'cart.updated', whCart(['id' => 'c_2', 'phone' => null]), $store['secret'])->assertOk();

    expect(DB::table('consent_records')->count())->toBe(0)
        ->and(DB::table('carts')->count())->toBe(2)
        ->and(DB::table('carts')->where('external_id', 'c_1')->value('phone_hash'))->toBeNull();
});

it('marks a cart purchased and removes a deleted product', function () {
    $store = whStore();

    whPost($store['store'], 'cart.updated', whCart(), $store['secret'])->assertOk();
    whPost($store['store'], 'cart.purchased', ['id' => 'c_01jbx8', 'order_id' => '789'], $store['secret'])->assertOk();
    whPost($store['store'], 'product.deleted', ['id' => '123'], $store['secret'])->assertOk();
    whPost($store['store'], 'some.future.event', ['x' => 1], $store['secret'])->assertOk();
    whPost($store['store'], 'plugin.deactivated', [], $store['secret'])->assertOk();

    expect(DB::table('carts')->value('status'))->toBe('purchased')
        ->and(DB::table('audit_events')->where('action', 'store.connector_deactivated')->exists())->toBeTrue()
        ->and(WebhookEvent::query()->where('status', '!=', 'processed')->count())->toBe(0);
});
