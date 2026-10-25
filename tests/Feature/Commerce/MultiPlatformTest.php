<?php

use App\Models\Team;
use App\Modules\Commerce\Commerce;
use App\Modules\Commerce\CommerceAdapter;
use App\Modules\Commerce\CommerceAdapters;
use App\Modules\Commerce\Data\CartData;
use App\Modules\Commerce\Data\PageWindow;
use App\Modules\Commerce\Data\TokenSet;
use App\Modules\Commerce\Events\StoreConnected;
use App\Modules\Commerce\Events\StoreDisconnected;
use App\Modules\Commerce\Jobs\BackfillStore;
use App\Modules\Commerce\Jobs\FetchStoreProfile;
use App\Modules\Commerce\Jobs\ReconcileStore;
use App\Modules\Commerce\Jobs\RefreshStoreToken;
use App\Modules\Commerce\Jobs\SyncCatalog;
use App\Modules\Commerce\LiveCartStatus;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\PlatformRateLimited;
use App\Modules\Commerce\Salla\SallaAdapter;
use App\Modules\Commerce\StoreAlreadyConnected;
use App\Modules\Commerce\StoreConnections;
use App\Modules\Commerce\StoreStatus;
use App\Modules\Compliance\Jobs\PurgeExpiredData;
use App\Modules\Knowledge\Jobs\ReindexCatalog;
use App\Modules\Measurement\Jobs\WeeklyMeasurement;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Tenancy\MissingTenantContext;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Stands in for the WooCommerce adapter: records every call, answers from
 * static stubs, and can be told to hit a rate limit.
 */
final class FakePlatformAdapter implements CommerceAdapter
{
    /** @var list<string> */
    public static array $calls = [];

    public static ?int $rateLimitAfter = null;

    public static bool $livePurchased = false;

    public static function reset(): void
    {
        self::$calls = [];
        self::$rateLimitAfter = null;
        self::$livePurchased = false;
    }

    private function call(string $method): void
    {
        self::$calls[] = $method;

        if (self::$rateLimitAfter !== null) {
            throw new PlatformRateLimited(self::$rateLimitAfter, 'Fake');
        }
    }

    public function abandonedCarts(StoreConnection $store, int $sinceMinutes, ?PageWindow $window = null): iterable
    {
        $this->call('abandonedCarts');

        return [new CartData('woo-cart-1', null, 15000, 'SAR', 'https://shop.test/checkout', [], CarbonImmutable::now())];
    }

    public function abandonedCart(StoreConnection $store, string $externalId): ?CartData
    {
        $this->call('abandonedCart');

        return new CartData($externalId, null, 15000, 'SAR', null, [], null, purchased: self::$livePurchased);
    }

    public function ordersSince(StoreConnection $store, CarbonImmutable $since, ?PageWindow $window = null): iterable
    {
        $this->call('ordersSince');

        return [];
    }

    public function products(StoreConnection $store, ?PageWindow $window = null): iterable
    {
        $this->call('products');

        return [];
    }

    public function categories(StoreConnection $store, ?PageWindow $window = null): iterable
    {
        $this->call('categories');

        return [['external_id' => 'c1', 'name' => 'عطور']];
    }

    public function storeProfile(StoreConnection $store): array
    {
        $this->call('storeProfile');

        return ['name' => 'متجر ووكومرس', 'email' => 'owner@woo.test'];
    }

    public function refreshTokens(StoreConnection $store): TokenSet
    {
        $this->call('refreshTokens');

        throw new LogicException('WooCommerce keys never expire.');
    }
}

beforeEach(function () {
    FakePlatformAdapter::reset();
    config(['codex.commerce_adapters.woocommerce' => FakePlatformAdapter::class]);

    // Any Salla call here would be a bug: the woo store must never reach Salla.
    Http::fake(fn () => Http::response('stray request', 500));
});

function multiWorkspace(string $name = 'مساحة'): int
{
    return Team::create(['name' => $name, 'is_personal' => false])->id;
}

function multiAs(int $tenantId, Closure $callback): mixed
{
    return app(TenantContext::class)->run($tenantId, $callback);
}

/** A tenant with a connected WooCommerce store; the connect side effects are faked away. */
function wooTenant(string $storeId = 'woo_01jtest'): int
{
    $tenantId = multiWorkspace();

    $bus = Bus::getFacadeRoot();
    Bus::fake([FetchStoreProfile::class, BackfillStore::class]);

    multiAs($tenantId, fn () => app(StoreConnections::class)->connect(TenantRoute::WOOCOMMERCE, $storeId, [
        'site_url' => 'https://shop.test',
        'credentials' => ['consumer_key' => 'ck_x', 'consumer_secret' => 'cs_x', 'connector_secret' => str_repeat('a', 64)],
    ]));

    Bus::swap($bus);
    FakePlatformAdapter::reset();

    return $tenantId;
}

function sallaTenant(string $merchant = '777'): int
{
    $tenantId = multiWorkspace('Salla '.$merchant);
    TenantRoute::bind(TenantRoute::SALLA, $merchant, $tenantId);

    multiAs($tenantId, fn () => StoreConnection::query()->create([
        'provider' => TenantRoute::SALLA,
        'external_store_id' => $merchant,
        'access_token' => 'a',
        'refresh_token' => 'r',
        'access_expires_at' => now()->addHour(),
        'status' => StoreStatus::Active,
    ]));

    return $tenantId;
}

it('resolves the adapter from the store provider', function () {
    $adapters = app(CommerceAdapters::class);

    expect($adapters->for(new StoreConnection(['provider' => TenantRoute::SALLA])))->toBeInstanceOf(SallaAdapter::class)
        ->and($adapters->for(new StoreConnection(['provider' => TenantRoute::WOOCOMMERCE])))->toBeInstanceOf(FakePlatformAdapter::class);

    expect(fn () => $adapters->for(new StoreConnection(['provider' => 'zid'])))->toThrow(InvalidArgumentException::class);
});

it('runs reconcile, catalog sync and the profile fetch through the store own adapter', function () {
    $tenantId = wooTenant();

    ReconcileStore::dispatch($tenantId);
    SyncCatalog::dispatch($tenantId);
    FetchStoreProfile::dispatchSync($tenantId);

    expect(FakePlatformAdapter::$calls)->toContain('categories', 'abandonedCarts', 'ordersSince', 'products', 'storeProfile')
        ->and(DB::table('carts')->where('tenant_id', $tenantId)->value('external_id'))->toBe('woo-cart-1')
        ->and(DB::table('categories')->where('tenant_id', $tenantId)->value('name'))->toBe('عطور')
        ->and(Team::find($tenantId)->name)->toBe('متجر ووكومرس')
        ->and(DB::table('store_connections')->value('owner_email'))->toBe('owner@woo.test');

    Http::assertNothingSent();
});

it('finishes a backfill through the store own adapter', function () {
    $tenantId = wooTenant();

    Bus::dispatchSync(new BackfillStore($tenantId));

    expect(FakePlatformAdapter::$calls)->toBe(['categories', 'products', 'abandonedCarts', 'ordersSince'])
        ->and(DB::table('store_connections')->value('last_reconciled_at'))->not->toBeNull();
});

it('releases a job on any platform rate limit', function (string $job) {
    $tenantId = wooTenant();
    FakePlatformAdapter::$rateLimitAfter = 42;

    $instance = (new $job($tenantId))->withFakeQueueInteractions();
    multiAs($tenantId, fn () => app()->call([$instance, 'handle']));

    $instance->assertReleased(42);
})->with([ReconcileStore::class, SyncCatalog::class, BackfillStore::class]);

it('asks the store own platform about a cart before sending', function () {
    $tenantId = wooTenant();
    ReconcileStore::dispatch($tenantId);
    FakePlatformAdapter::$livePurchased = true;

    $status = multiAs($tenantId, fn () => app(Commerce::class)->liveCartStatus((int) DB::table('carts')->value('id')));

    expect($status)->toBe(LiveCartStatus::Purchased)
        ->and(FakePlatformAdapter::$calls)->toContain('abandonedCart')
        ->and(DB::table('carts')->value('status'))->toBe('purchased');
});

it('never refreshes tokens for a WooCommerce store', function () {
    $tenantId = wooTenant();

    RefreshStoreToken::dispatch($tenantId);

    expect(FakePlatformAdapter::$calls)->toBe([])
        ->and(DB::table('store_connections')->value('status'))->toBe('active');
});

it('lists tenants of every commerce platform, and only those', function () {
    $salla = sallaTenant();
    $woo = wooTenant();
    TenantRoute::bind(TenantRoute::META, 'waba-1', multiWorkspace());

    expect(TenantRoute::commerceTenants())->toEqualCanonicalizing([$salla, $woo]);
});

it('connects a store to the current workspace and starts the first pull', function () {
    Event::fake([StoreConnected::class]);
    Bus::fake([FetchStoreProfile::class, BackfillStore::class]);
    $tenantId = multiWorkspace('مساحة المالك');

    $connection = multiAs($tenantId, fn () => app(StoreConnections::class)->connect(TenantRoute::WOOCOMMERCE, 'woo_01jabc', [
        'site_url' => 'https://shop.test',
        'credentials' => ['consumer_key' => 'ck_x', 'consumer_secret' => 'cs_secret', 'connector_secret' => str_repeat('b', 64)],
        'tenant_id' => 999_999,
    ], actorId: 5));

    $row = DB::table('store_connections')->first();

    expect($connection->tenant_id)->toBe($tenantId)
        ->and(TenantRoute::tenantFor(TenantRoute::WOOCOMMERCE, 'woo_01jabc'))->toBe($tenantId)
        ->and($row->status)->toBe('active')
        ->and($row->site_url)->toBe('https://shop.test')
        ->and($row->credentials)->not->toContain('cs_secret')
        ->and($connection->credentials['consumer_secret'])->toBe('cs_secret')
        ->and(json_encode($connection->toArray()))->not->toContain('cs_secret')
        // The workspace keeps its name and members; only one team exists.
        ->and(Team::query()->where('is_personal', false)->count())->toBe(1)
        ->and(DB::table('audit_events')->where('action', 'store.connected')->value('actor_type'))->toBe('user');

    Event::assertDispatched(StoreConnected::class);
    Bus::assertDispatched(FetchStoreProfile::class, fn (FetchStoreProfile $job) => $job->tenantId === $tenantId);
    Bus::assertDispatched(BackfillStore::class, fn (BackfillStore $job) => $job->tenantId === $tenantId);
});

it('refuses a store that belongs to another workspace', function () {
    wooTenant('woo_01jtaken');
    $intruder = multiWorkspace('دخيل');

    expect(fn () => multiAs($intruder, fn () => app(StoreConnections::class)->connect(TenantRoute::WOOCOMMERCE, 'woo_01jtaken', [])))
        ->toThrow(StoreAlreadyConnected::class);

    expect(DB::table('store_connections')->count())->toBe(1)
        ->and(DB::table('store_connections')->value('tenant_id'))->not->toBe($intruder);
});

it('refuses a second live store on another platform', function () {
    $tenantId = sallaTenant();

    expect(fn () => multiAs($tenantId, fn () => app(StoreConnections::class)->connect(TenantRoute::WOOCOMMERCE, 'woo_01jsecond', [])))
        ->toThrow(StoreAlreadyConnected::class);

    expect(TenantRoute::tenantFor(TenantRoute::WOOCOMMERCE, 'woo_01jsecond'))->toBeNull();
});

it('needs a workspace to connect into', function () {
    expect(fn () => app(StoreConnections::class)->connect(TenantRoute::WOOCOMMERCE, 'woo_01jnone', []))
        ->toThrow(MissingTenantContext::class);
});

it('replaces the old store route when a workspace connects a new store', function () {
    $tenantId = wooTenant('woo_01jold');

    multiAs($tenantId, fn () => app(StoreConnections::class)->connect(TenantRoute::WOOCOMMERCE, 'woo_01jnew', []));

    expect(TenantRoute::tenantFor(TenantRoute::WOOCOMMERCE, 'woo_01jold'))->toBeNull()
        ->and(TenantRoute::tenantFor(TenantRoute::WOOCOMMERCE, 'woo_01jnew'))->toBe($tenantId)
        ->and(DB::table('store_connections')->count())->toBe(1);
});

it('drops the keys on disconnect', function () {
    Event::fake([StoreDisconnected::class]);
    $tenantId = wooTenant();

    multiAs($tenantId, fn () => app(StoreConnections::class)->disconnect(TenantRoute::WOOCOMMERCE, actorId: 5));

    $row = DB::table('store_connections')->first();

    expect($row->status)->toBe('uninstalled')
        ->and($row->credentials)->toBeNull()
        ->and(DB::table('audit_events')->where('action', 'store.disconnected')->exists())->toBeTrue();
    Event::assertDispatched(StoreDisconnected::class);
});

it('names a new workspace after its platform', function () {
    Bus::fake([FetchStoreProfile::class, BackfillStore::class]);
    $tokens = new TokenSet('a', 'r', CarbonImmutable::now()->addDay(), null);

    $salla = app(StoreConnections::class)->authorize(TenantRoute::SALLA, '42', $tokens);
    $woo = app(StoreConnections::class)->authorize(TenantRoute::WOOCOMMERCE, 'woo_01jx', $tokens);

    expect(Team::find($salla)->name)->toBe('Salla 42')
        ->and(Team::find($woo)->name)->toBe('WooCommerce woo_01jx');
});

it('fans the scheduled commerce jobs out over every commerce tenant', function () {
    $salla = sallaTenant();
    $woo = wooTenant();
    Queue::fake();

    foreach (app(Schedule::class)->events() as $event) {
        $event->run(app());
    }

    foreach ([ReconcileStore::class, SyncCatalog::class, ReindexCatalog::class, WeeklyMeasurement::class, PurgeExpiredData::class] as $job) {
        foreach ([$salla, $woo] as $tenantId) {
            Queue::assertPushed($job, fn ($pushed) => $pushed->tenantId === $tenantId);
        }
    }

    Queue::assertPushed(RefreshStoreToken::class, fn (RefreshStoreToken $job) => $job->tenantId === $salla);
    Queue::assertNotPushed(RefreshStoreToken::class, fn (RefreshStoreToken $job) => $job->tenantId === $woo);
});
