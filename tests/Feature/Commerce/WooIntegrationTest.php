<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Commerce\Jobs\ReconcileStore;
use App\Modules\Commerce\Jobs\SyncCatalog;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\PlatformRateLimited;
use App\Modules\Commerce\WooCommerce\WooAdapter;
use App\Modules\Commerce\WooCommerce\WooHostResolver;
use App\Modules\Commerce\WooCommerce\WooSignature;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

const WC_SITE = 'https://shop.example.com';

beforeEach(function () {
    config(['app.url' => 'https://api.codex.test']);

    $this->team = Team::factory()->create();
    $this->owner = User::factory()->create();
    $this->agent = User::factory()->create();
    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($this->agent, ['role' => TeamRole::Agent->value]);

    wcFake();
});

/**
 * One fake that reads its stubs at request time (overrides first), like the
 * Salla suite: a test can change the site's answers halfway.
 */
function wcFake(array $overrides = []): void
{
    $GLOBALS['wcStubs'] = $overrides + [
        'shop.example.com/wp-json/wc/v3/system_status*' => ['settings' => ['currency' => 'SAR']],
        'shop.example.com/wp-json/wc/v3/products/categories*' => [['id' => 15, 'name' => 'عود &amp; بخور']],
        'shop.example.com/wp-json/wc/v3/products/7/variations*' => [
            ['id' => 71, 'attributes' => [['option' => '50ml']], 'price' => '99', 'regular_price' => '99', 'sale_price' => '', 'manage_stock' => true, 'stock_quantity' => 2, 'stock_status' => 'instock'],
        ],
        'shop.example.com/wp-json/wc/v3/products*' => [wcProduct(5, 'مسك أبيض'), wcProduct(7, 'عود كمبودي', ['type' => 'variable'])],
        'shop.example.com/wp-json/wc/v3/orders*' => [],
        'shop.example.com/wp-json/codex/v1/carts*' => ['carts' => [], 'next_page' => null],
        'shop.example.com/wp-json/' => ['name' => 'متجر العود', 'namespaces' => ['wc/v3', 'codex/v1']],
    ];

    Http::fake(function (Request $request) {
        foreach ($GLOBALS['wcStubs'] as $pattern => $body) {
            if (Str::is('*'.$pattern, $request->url())) {
                return $body instanceof Closure ? $body($request) : Http::response($body);
            }
        }

        return Http::response('stray request', 500);
    });
}

function wcOverride(array $stubs): void
{
    $GLOBALS['wcStubs'] = $stubs + $GLOBALS['wcStubs'];
}

function wcProduct(int $id, string $name, array $overrides = []): array
{
    return $overrides + [
        'id' => $id, 'name' => $name, 'type' => 'simple', 'status' => 'publish',
        'price' => '89.00', 'regular_price' => '89.00', 'sale_price' => '',
        'manage_stock' => false, 'stock_quantity' => null, 'stock_status' => 'instock',
        'permalink' => WC_SITE."/p/{$id}", 'categories' => [['id' => 15]],
    ];
}

function wcConnect(?User $user = null, ?Team $team = null, array $input = [])
{
    $team ??= test()->team;

    return test()->actingAs($user ?? test()->owner)->postJson("/api/v1/teams/{$team->slug}/commerce/woocommerce", $input + [
        'site_url' => WC_SITE.'/',
        'consumer_key' => 'ck_'.str_repeat('a', 40),
        'consumer_secret' => 'cs_'.str_repeat('b', 40),
    ]);
}

function wcInTenant(int $tenantId, Closure $callback): mixed
{
    return app(TenantContext::class)->run($tenantId, $callback);
}

function wcStore(int $tenantId): StoreConnection
{
    return wcInTenant($tenantId, fn () => StoreConnection::query()->where('provider', 'woocommerce')->firstOrFail());
}

/** The request carried a valid protocol signature for this store's secret. */
function wcSignedCorrectly(Request $request, string $secret): bool
{
    $parts = parse_url($request->url());
    parse_str($parts['query'] ?? '', $query);
    $route = substr($parts['path'], strlen('/wp-json'));

    return $request->header('X-Codex-Signature')[0] === WooSignature::forGet($secret, (int) $request->header('X-Codex-Timestamp')[0], $route, $query);
}

it('connects a SAR store, returns the secret once and backfills it', function () {
    $response = wcConnect()->assertCreated();

    $storeId = $response->json('data.store_id');
    $secret = $response->json('data.connector_secret');

    expect($storeId)->toMatch('/^woo_[a-z0-9]{26}$/')
        ->and($secret)->toMatch('/^[a-f0-9]{64}$/')
        ->and($response->json('data.api_base'))->toBe('https://api.codex.test')
        ->and($response->json('data.webhook_url'))->toBe("https://api.codex.test/webhooks/woocommerce/{$storeId}")
        ->and(TenantRoute::tenantFor(TenantRoute::WOOCOMMERCE, $storeId))->toBe($this->team->id);

    $row = DB::table('store_connections')->first();
    expect($row->site_url)->toBe(WC_SITE)
        ->and($row->status)->toBe('active')
        ->and($row->store_name)->toBe('متجر العود')
        ->and($row->credentials)->not->toContain('ck_')->not->toContain($secret)
        ->and(wcStore($this->team->id)->credentials['connector_secret'])->toBe($secret);

    // Backfill: categories, products with variations, carts from the plugin (signed).
    expect(DB::table('categories')->where('tenant_id', $this->team->id)->value('name'))->toBe('عود & بخور')
        ->and(DB::table('products')->where('tenant_id', $this->team->id)->count())->toBe(2)
        ->and(json_decode(DB::table('products')->where('external_id', '7')->value('variants'), true)[0]['name'] ?? null)->toBe('50ml');
    Http::assertSent(fn (Request $request) => str_contains($request->url(), '/wp-json/codex/v1/carts') && wcSignedCorrectly($request, $secret));
    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'wc/v3/system_status') && $request->hasHeader('Authorization'));

    // The status shows the platform and site, never a key or secret.
    $status = $this->actingAs($this->owner)->getJson("/api/v1/teams/{$this->team->slug}/commerce/store")->assertOk();
    expect($status->json('data.connected'))->toBeTrue()
        ->and($status->json('data.store.provider'))->toBe('woocommerce')
        ->and($status->json('data.store.site_url'))->toBe(WC_SITE)
        ->and($status->getContent())->not->toContain($secret)->not->toContain('cs_')->not->toContain('ck_');
});

it('refuses keys WooCommerce rejects', function () {
    wcOverride(['shop.example.com/wp-json/wc/v3/*' => fn () => Http::response(['code' => 'woocommerce_rest_cannot_view'], 401)]);

    wcConnect()->assertStatus(422)->assertJsonPath('reason', 'bad_keys')->assertJsonValidationErrors('consumer_key');

    expect(DB::table('store_connections')->count())->toBe(0);
});

it('falls back to query-string keys when the host strips the Authorization header', function () {
    wcOverride(['shop.example.com/wp-json/wc/v3/system_status*' => fn (Request $request) => str_contains($request->url(), 'consumer_key=ck_')
        ? Http::response(['settings' => ['currency' => 'SAR']])
        : Http::response(['code' => 'woocommerce_rest_cannot_view'], 401)]);

    wcConnect()->assertCreated();
});

it('refuses a store that is not in SAR', function () {
    wcOverride(['shop.example.com/wp-json/wc/v3/system_status*' => ['settings' => ['currency' => 'USD']]]);

    wcConnect()->assertStatus(422)->assertJsonPath('reason', 'currency');

    expect(DB::table('store_connections')->count())->toBe(0);
});

it('reads the currency without system_status rights', function () {
    wcOverride([
        'shop.example.com/wp-json/wc/v3/system_status*' => fn () => Http::response(['code' => 'forbidden'], 403),
        'shop.example.com/wp-json/wc/v3/data/currencies/current*' => ['code' => 'EGP'],
    ]);

    wcConnect()->assertStatus(422)->assertJsonPath('reason', 'currency');
});

it('accepts only public https site addresses', function (string $url) {
    wcConnect(input: ['site_url' => $url])->assertStatus(422)->assertJsonValidationErrors('site_url');
})->with(['http://shop.example.com', 'https://localhost', 'https://10.0.0.5', 'https://127.0.0.1', 'https://user:pass@shop.example.com', 'https://shop.example.com/?a=1', 'shop.example.com', 'https://127.1', 'https://0177.0.0.1', 'https://[::1]', 'https://169.254.169.254', 'https://100.64.0.1']);

it('refuses a site whose name resolves inside, and makes no request', function () {
    // What WooHostResolver answers for a name that points at a private or reserved address.
    app()->instance(WooHostResolver::class, new class extends WooHostResolver
    {
        public function publicAddresses(string $host): array
        {
            return [];
        }
    });

    wcConnect()->assertStatus(422)->assertJsonPath('reason', 'unreachable');

    Http::assertNothingSent();
    expect(DB::table('store_connections')->count())->toBe(0);
});

it('lets through only public addresses when it resolves', function () {
    $resolver = new WooHostResolver;

    expect($resolver->publicAddresses('93.184.215.14'))->toBe(['93.184.215.14'])
        ->and($resolver->publicAddresses('[2606:4700::1111]'))->toBe(['2606:4700::1111']);

    foreach (['127.0.0.1', '10.1.2.3', '172.16.0.1', '192.168.1.1', '169.254.169.254', '100.64.0.1', '198.18.0.1', '0.0.0.0', '[::1]', '[fd00:ec2::254]', '[::ffff:127.0.0.1]'] as $address) {
        expect($resolver->publicAddresses($address))->toBe([], $address);
    }
});

it('refuses a site already connected to another workspace', function () {
    wcConnect()->assertCreated();

    $other = Team::factory()->create();
    $other->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);

    wcConnect(team: $other)->assertStatus(409)->assertJsonPath('reason', 'other_workspace');

    expect(DB::table('store_connections')->where('tenant_id', $other->id)->count())->toBe(0);
});

it('lets only members who manage integrations connect', function () {
    wcConnect(user: $this->agent)->assertForbidden();
    $this->actingAs($this->agent)->getJson("/api/v1/teams/{$this->team->slug}/commerce/store")->assertForbidden();
});

it('rotates the connector secret and disconnects', function () {
    $first = wcConnect()->assertCreated();
    $storeId = $first->json('data.store_id');

    $rotated = $this->actingAs($this->owner)->postJson("/api/v1/teams/{$this->team->slug}/commerce/woocommerce/rotate-secret")->assertOk();

    expect($rotated->json('data.store_id'))->toBe($storeId)
        ->and($rotated->json('data.connector_secret'))->not->toBe($first->json('data.connector_secret'))
        ->and(wcStore($this->team->id)->credentials['consumer_key'])->toStartWith('ck_');

    $this->actingAs($this->owner)->deleteJson("/api/v1/teams/{$this->team->slug}/commerce/woocommerce")->assertOk();

    $row = DB::table('store_connections')->first();
    expect($row->status)->toBe('uninstalled')
        ->and($row->credentials)->toBeNull()
        ->and($this->actingAs($this->owner)->getJson("/api/v1/teams/{$this->team->slug}/commerce/store")->json('data.connected'))->toBeFalse();

    $this->actingAs($this->owner)->postJson("/api/v1/teams/{$this->team->slug}/commerce/woocommerce/rotate-secret")->assertNotFound();

    // Reconnecting keeps the Store ID, so the plugin needs only the new secret.
    expect(wcConnect()->assertCreated()->json('data.store_id'))->toBe($storeId);
});

it('syncs the catalog and removes products the site no longer has', function () {
    wcConnect()->assertCreated();
    wcOverride(['shop.example.com/wp-json/wc/v3/products?*' => [wcProduct(5, 'مسك أبيض', ['price' => '120.00', 'regular_price' => '150.00', 'sale_price' => '120.00'])]]);

    Bus::dispatchSync(new SyncCatalog($this->team->id));

    expect(DB::table('products')->where('external_id', '7')->value('status'))->toBe('deleted')
        ->and(DB::table('products')->where('external_id', '5')->value('price_halalas'))->toBe(12000)
        ->and(DB::table('products')->where('external_id', '5')->value('regular_price_halalas'))->toBe(15000);
});

it('reconciles carts from the plugin and orders from the REST API', function () {
    $secret = wcConnect()->assertCreated()->json('data.connector_secret');

    wcOverride([
        'shop.example.com/wp-json/codex/v1/carts*' => ['carts' => [
            ['id' => 'c_new', 'status' => 'open', 'total' => '150.00', 'items' => [], 'phone' => '+966551234567', 'consent' => true, 'recovery_url' => WC_SITE.'/?codex_cart=x'],
            ['id' => 'c_old', 'status' => 'expired', 'total' => '10.00', 'items' => []],
        ], 'next_page' => null],
        'shop.example.com/wp-json/wc/v3/orders*' => [['id' => 900, 'status' => 'processing', 'total' => '300.00', 'date_created_gmt' => now()->utc()->format('Y-m-d\TH:i:s'), 'billing' => ['phone' => '0551234567']]],
    ]);

    Bus::dispatchSync(new ReconcileStore($this->team->id));

    expect(DB::table('carts')->pluck('external_id')->all())->toBe(['c_new'])
        ->and(DB::table('carts')->value('discovered_by'))->toBe('reconciliation')
        ->and(DB::table('orders')->value('total_halalas'))->toBe(30000)
        ->and(DB::table('store_connections')->value('reconciliation_gap_count'))->toBe(2)
        // Pulled before its webhook: the consent box is recorded too, so recovery does not see "no consent".
        ->and(DB::table('consent_records')->where('source', 'checkout_opt_in')->where('status', 'granted')->count())->toBe(1);
    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'wc/v3/orders') && str_contains($request->url(), 'after='));
    Http::assertSent(fn (Request $request) => str_contains($request->url(), 'codex/v1/carts?') && wcSignedCorrectly($request, $secret));
});

it('turns a WooCommerce 429 into a platform rate limit with its Retry-After', function () {
    wcConnect()->assertCreated();
    wcOverride(['shop.example.com/wp-json/wc/v3/products?*' => fn () => Http::response(['code' => 'too_many'], 429, ['Retry-After' => '42'])]);
    $store = wcStore($this->team->id);

    try {
        iterator_to_array(app(WooAdapter::class)->products($store));
        $this->fail('Expected a rate limit.');
    } catch (PlatformRateLimited $exception) {
        expect($exception->retryAfterSeconds)->toBe(42);
    }

    // A catalog sync cut short deletes nothing.
    wcInTenant($this->team->id, fn () => app()->call([new SyncCatalog($this->team->id), 'handle']));
    expect(DB::table('products')->where('status', 'deleted')->count())->toBe(0);
});

it('asks the plugin for one cart: gone on 404 or expiry, purchased when bought', function () {
    wcConnect()->assertCreated();
    $store = wcStore($this->team->id);
    $adapter = app(WooAdapter::class);

    wcOverride(['shop.example.com/wp-json/codex/v1/carts/c_404*' => fn () => Http::response(['code' => 'not_found'], 404)]);
    wcOverride(['shop.example.com/wp-json/codex/v1/carts/c_exp*' => ['id' => 'c_exp', 'status' => 'expired']]);
    wcOverride(['shop.example.com/wp-json/codex/v1/carts/c_buy*' => ['id' => 'c_buy', 'status' => 'purchased', 'total' => '10.00']]);
    wcOverride(['shop.example.com/wp-json/codex/v1/carts/c_open*' => ['id' => 'c_open', 'status' => 'open', 'total' => '10.00']]);

    expect($adapter->abandonedCart($store, 'c_404'))->toBeNull()
        ->and($adapter->abandonedCart($store, 'c_exp'))->toBeNull()
        ->and($adapter->abandonedCart($store, '../../wc/v3/orders'))->toBeNull()
        ->and($adapter->abandonedCart($store, 'c_buy')?->purchased)->toBeTrue()
        ->and($adapter->abandonedCart($store, 'c_open')?->purchased)->toBeFalse();
});

it('has no tokens to refresh', function () {
    wcConnect()->assertCreated();

    expect(fn () => app(WooAdapter::class)->refreshTokens(wcStore($this->team->id)))->toThrow(LogicException::class);
});
