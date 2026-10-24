<?php

use App\Models\Team;
use App\Modules\Commerce\Events\CartAbandoned;
use App\Modules\Commerce\Events\CartPurchased;
use App\Modules\Commerce\Events\OrderPlaced;
use App\Modules\Commerce\Events\StoreDisconnected;
use App\Modules\Commerce\Jobs\ReconcileStore;
use App\Modules\Commerce\Jobs\RefreshStoreToken;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

const MERCHANT = 1234509876;

beforeEach(function () {
    config([
        'services.salla.client_id' => 'client',
        'services.salla.client_secret' => 'secret',
    ]);

    fakeSalla();
});

/**
 * One fake for the whole test that reads its stubs at request time, so a test
 * can change Salla's answers halfway (Http::fake stubs registered later never
 * win over earlier ones). Overrides first, then harmless empty defaults.
 */
function fakeSalla(array $overrides = []): void
{
    $GLOBALS['sallaStubs'] = $overrides + [
        'accounts.salla.sa/oauth2/user/info' => ['data' => ['email' => 'owner@store.sa', 'merchant' => ['name' => 'عطور الريم']]],
        'api.salla.dev/*' => ['data' => [], 'pagination' => ['totalPages' => 1]],
    ];

    Http::fake(function (Request $request) {
        foreach ($GLOBALS['sallaStubs'] as $pattern => $body) {
            if (Str::is('*'.$pattern, $request->url())) {
                return $body instanceof Closure ? $body() : Http::response(...(array_is_list($body) && count($body) === 2 && is_int($body[1]) ? $body : [$body]));
            }
        }

        return Http::response('stray request', 500);
    });
}

function overrideSalla(array $stubs): void
{
    $GLOBALS['sallaStubs'] = $stubs + $GLOBALS['sallaStubs'];
}

function sallaWebhook(string $event, array $data = [], int $merchant = MERCHANT)
{
    $body = json_encode(['event' => $event, 'merchant' => $merchant, 'created_at' => now()->toDateTimeString(), 'data' => $data]);

    return test()->call('POST', '/webhooks/salla', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SALLA_SIGNATURE' => hash_hmac('sha256', $body, 'testing-salla-secret'),
    ], content: $body);
}

function installStore(): int
{
    sallaWebhook('app.store.authorize', [
        'access_token' => 'access-1',
        'refresh_token' => 'refresh-1',
        'expires' => now()->addDays(14)->timestamp,
        'scope' => 'offline_access carts.read orders.read',
    ])->assertOk();

    return TenantRoute::tenantFor(TenantRoute::SALLA, MERCHANT);
}

function asTenant(int $tenantId, Closure $callback): mixed
{
    return app(TenantContext::class)->run($tenantId, $callback);
}

it('creates one workspace per store, even when Salla authorizes twice', function () {
    $tenantId = installStore();
    installStore();

    expect(Team::query()->where('is_personal', false)->count())->toBe(1)
        ->and(Team::find($tenantId)->name)->toBe('عطور الريم');

    $connection = DB::table('store_connections')->first();

    expect($connection->status)->toBe('active')
        ->and($connection->owner_email)->toBe('owner@store.sa')
        ->and($connection->access_token)->not->toContain('access-1');
});

it('records an abandoned cart with its customer and announces it once', function () {
    Event::fake([CartAbandoned::class]);
    $tenantId = installStore();

    $cart = [
        'id' => 555,
        'total' => ['amount' => 420, 'currency' => 'SAR'],
        'urls' => ['checkout' => 'https://store.sa/checkout/555'],
        'customer' => ['id' => 42, 'mobile' => '512345678', 'mobile_code' => '+966'],
        'items' => [['product_id' => 9, 'quantity' => 2, 'amounts' => ['price' => ['amount' => 210]]]],
    ];

    sallaWebhook('abandoned.cart', $cart)->assertOk();
    sallaWebhook('abandoned.cart', $cart + ['updated_at' => 'later'])->assertOk();

    Event::assertDispatchedTimes(CartAbandoned::class, 1);

    $row = DB::table('carts')->first();

    expect($row->tenant_id)->toBe($tenantId)
        ->and($row->total_halalas)->toBe(42000)
        ->and($row->checkout_url)->toBe('https://store.sa/checkout/555')
        ->and($row->phone_hash)->toHaveLength(64)
        ->and(json_encode(DB::table('customers')->first()))->not->toContain('512345678');
});

it('marks a cart purchased and announces it', function () {
    Event::fake([CartPurchased::class]);
    installStore();

    sallaWebhook('abandoned.cart', ['id' => 555, 'customer' => ['mobile' => '+966512345678']]);
    sallaWebhook('abandoned.cart.purchased', ['id' => 555]);

    expect(DB::table('carts')->value('status'))->toBe('purchased');
    Event::assertDispatched(CartPurchased::class);
});

it('records orders and announces new ones', function () {
    Event::fake([OrderPlaced::class]);
    installStore();

    $order = ['id' => 77, 'amounts' => ['total' => ['amount' => 300]], 'customer' => ['mobile' => '+966512345678']];

    sallaWebhook('order.created', $order);
    sallaWebhook('order.updated', $order);

    expect(DB::table('orders')->count())->toBe(1);
    Event::assertDispatchedTimes(OrderPlaced::class, 1);
});

it('ignores events for a store that never installed', function () {
    sallaWebhook('abandoned.cart', ['id' => 1], merchant: 999)->assertOk();

    expect(DB::table('carts')->count())->toBe(0)
        ->and(DB::table('webhook_events')->value('tenant_id'))->toBeNull();
});

it('keeps one store data away from another store', function () {
    $tenantA = installStore();
    sallaWebhook('abandoned.cart', ['id' => 1, 'customer' => ['mobile' => '+966512345678']]);

    sallaWebhook('app.store.authorize', ['access_token' => 'b', 'refresh_token' => 'b', 'expires_in' => 3600], merchant: 222);
    $tenantB = TenantRoute::tenantFor(TenantRoute::SALLA, 222);

    expect($tenantB)->not->toBe($tenantA)
        ->and(asTenant($tenantB, fn () => DB::table('carts')->where('tenant_id', $tenantB)->count()))->toBe(0);
});

it('stops and drops tokens on uninstall', function () {
    Event::fake([StoreDisconnected::class]);
    installStore();

    sallaWebhook('app.uninstalled');

    $connection = DB::table('store_connections')->first();

    expect($connection->status)->toBe('uninstalled')
        ->and($connection->access_token)->toBeNull();
    Event::assertDispatched(StoreDisconnected::class);
});

it('refreshes a token exactly once, however many jobs race', function () {
    $tenantId = installStore();
    DB::table('store_connections')->update(['access_expires_at' => now()->addHour()]);

    overrideSalla(['accounts.salla.sa/oauth2/token' => ['access_token' => 'access-2', 'refresh_token' => 'refresh-2', 'expires_in' => 1209600]]);
    $before = count(Http::recorded());

    foreach (range(1, 10) as $ignored) {
        RefreshStoreToken::dispatch($tenantId);
    }

    $refreshCalls = collect(Http::recorded())->slice($before)->filter(fn ($pair) => str_contains($pair[0]->url(), 'oauth2/token'));

    expect($refreshCalls)->toHaveCount(1)
        ->and(DB::table('store_connections')->value('status'))->toBe('active');
});

it('does nothing while another refresh holds the lock', function () {
    $tenantId = installStore();
    DB::table('store_connections')->update(['access_expires_at' => now()->addHour()]);
    overrideSalla(['accounts.salla.sa/oauth2/token' => ['access_token' => 'x', 'refresh_token' => 'y', 'expires_in' => 60]]);

    $lock = Cache::lock("commerce:token-refresh:{$tenantId}", 60);
    $lock->get();

    RefreshStoreToken::dispatch($tenantId);

    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), 'oauth2/token'));
    $lock->release();
});

it('stops sending and asks to reconnect when a refresh fails', function () {
    Event::fake([StoreDisconnected::class]);
    $tenantId = installStore();
    DB::table('store_connections')->update(['access_expires_at' => now()->addHour()]);
    overrideSalla(['accounts.salla.sa/oauth2/token' => fn () => Http::response(['error' => 'invalid_grant'], 400)]);

    RefreshStoreToken::dispatch($tenantId);

    expect(DB::table('store_connections')->value('status'))->toBe('needs_reauth');
    Event::assertDispatched(StoreDisconnected::class);
    expect(DB::table('audit_events')->where('action', 'store.needs_reauth')->exists())->toBeTrue();
});

it('counts carts found by polling as reconciliation gaps', function () {
    Event::fake([CartAbandoned::class]);

    $tenantId = installStore();
    sallaWebhook('abandoned.cart', ['id' => 1, 'customer' => ['mobile' => '+966512345678']]);

    // Cart 2 never came as a webhook: only polling finds it.
    overrideSalla(['api.salla.dev/admin/v2/carts/abandoned*' => [
        'data' => [
            ['id' => 1, 'customer' => ['mobile' => '+966512345678']],
            ['id' => 2, 'customer' => ['mobile' => '+966512345679']],
        ],
        'pagination' => ['totalPages' => 1],
    ]]);

    ReconcileStore::dispatch($tenantId);

    expect(DB::table('carts')->count())->toBe(2)
        ->and(DB::table('store_connections')->value('reconciliation_gap_count'))->toBe(1)
        ->and(DB::table('carts')->where('external_id', '2')->value('discovered_by'))->toBe('reconciliation');
    Event::assertDispatchedTimes(CartAbandoned::class, 2);
});
