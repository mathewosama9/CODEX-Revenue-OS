<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Modules\Measurement\ExperimentAssigner;
use App\Modules\Recovery\ScheduledActionDispatcher;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * The whole recovery path on a WooCommerce store: the plugin pushes a
 * consented cart, the first message is scheduled, and right before sending
 * we ask the plugin whether the cart is still open.
 */
beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-10 14:00', 'Asia/Riyadh'));

    $this->team = Team::factory()->create(['name' => 'متجر العود']);
    $owner = User::factory()->create();
    $this->team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $t = $this->team->id;
    $this->storeId = 'woo_'.strtolower((string) Str::ulid());
    $this->secret = bin2hex(random_bytes(32));

    app(TenantContext::class)->run($t, fn () => StoreConnection::query()->create([
        'provider' => 'woocommerce',
        'external_store_id' => $this->storeId,
        'site_url' => 'https://shop.example.com',
        'credentials' => ['consumer_key' => 'ck_x', 'consumer_secret' => 'cs_x', 'connector_secret' => $this->secret],
        'status' => 'active',
    ]));
    TenantRoute::bind(TenantRoute::WOOCOMMERCE, $this->storeId, $t);

    $account = DB::table('whatsapp_accounts')->insertGetId(['tenant_id' => $t, 'waba_id' => 'w1', 'access_token' => encrypt('t', false), 'status' => 'active']);
    DB::table('whatsapp_numbers')->insert(['tenant_id' => $t, 'whatsapp_account_id' => $account, 'phone_number_id' => 'pn1', 'quality_rating' => 'GREEN', 'status' => 'active']);

    foreach (['cart_recovery_1' => 'r1', 'cart_recovery_2' => 'r2', 'cart_recovery_2_discount' => 'r2d'] as $purpose => $name) {
        DB::table('message_templates')->insert(['tenant_id' => $t, 'whatsapp_account_id' => $account, 'name' => $name, 'language' => 'ar', 'category' => 'MARKETING', 'status' => 'APPROVED', 'purpose' => $purpose, 'components' => '[]']);
    }

    DB::table('category_margins')->insert(['tenant_id' => $t, 'category_external_id' => null, 'margin_bps' => 4000, 'effective_from' => now()->subYear()]);
    DB::table('recovery_settings')->insert(['tenant_id' => $t, 'enabled' => true]);

    app(TenantContext::class)->run($t, fn () => app(ExperimentAssigner::class)->experiment());
    DB::table('experiments')->update(['holdout_bps' => 0]);

    $this->sent = [];
    $this->pluginAsked = [];
    $this->liveStatus = 'open';

    Http::fake(function (Request $request) {
        if (str_contains($request->url(), 'graph.facebook.com')) {
            $this->sent[] = $request->data();

            return Http::response(['messages' => [['id' => 'wamid.'.count($this->sent)]]]);
        }

        if (str_starts_with($request->url(), 'https://shop.example.com/wp-json/codex/v1/carts/c_live')) {
            $this->pluginAsked[] = $request;

            if ($this->liveStatus === 'not_found') {
                return Http::response(['code' => 'not_found'], 404);
            }

            if ($this->liveStatus === 'down') {
                return Http::response('Service Unavailable', 503);
            }

            return Http::response(['id' => 'c_live', 'status' => $this->liveStatus, 'total' => '420.00', 'items' => []]);
        }

        return Http::response('stray request', 500);
    });
});

function wrPushCart(): void
{
    $body = json_encode(['event' => 'cart.updated', 'occurred_at' => now()->toIso8601String(), 'plugin_version' => '1.0.0', 'data' => [
        'id' => 'c_live',
        'status' => 'open',
        'currency' => 'SAR',
        'total' => '420.00',
        'items' => [['product_id' => '123', 'name' => 'عود كمبودي', 'quantity' => 1, 'unit_price' => '420.00']],
        'phone' => '+966551234567',
        'consent' => true,
        'consent_text' => 'أوافق على استلام رسائل واتساب من المتجر عن طلبي والعروض.',
        'consent_text_version' => 'a1b2c3d4',
        'recovery_url' => 'https://shop.example.com/?codex_cart=tok',
        'customer_id' => null,
        'abandoned_at' => now()->utc()->toIso8601String(),
    ]]);
    $timestamp = now()->getTimestamp();

    test()->call('POST', '/webhooks/woocommerce/'.test()->storeId, server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CODEX_TIMESTAMP' => (string) $timestamp,
        'HTTP_X_CODEX_SIGNATURE' => 'sha256='.hash_hmac('sha256', "{$timestamp}.{$body}", test()->secret),
        'HTTP_X_CODEX_EVENT_ID' => (string) Str::uuid(),
    ], content: $body)->assertOk();
}

it('sends the first message with the plugin recovery link while the cart is open', function () {
    wrPushCart();

    expect(DB::table('recovery_sequences')->value('status'))->toBe('active')
        ->and(DB::table('scheduled_actions')->value('status'))->toBe('pending');

    $this->travel(61)->minutes();
    app(ScheduledActionDispatcher::class)->dispatchDue();

    expect($this->pluginAsked)->toHaveCount(1)
        ->and($this->sent)->toHaveCount(1)
        ->and($this->sent[0]['template']['components'][0]['parameters'][1]['text'])->toBe('https://shop.example.com/?codex_cart=tok');
});

it('cancels the message when the plugin says the cart was bought meanwhile', function () {
    wrPushCart();
    $this->liveStatus = 'purchased';

    $this->travel(61)->minutes();
    app(ScheduledActionDispatcher::class)->dispatchDue();

    expect($this->pluginAsked)->toHaveCount(1)
        ->and($this->sent)->toBe([])
        ->and(DB::table('carts')->value('status'))->toBe('purchased')
        ->and(DB::table('recovery_sequences')->value('reason'))->toBe('purchased')
        ->and(DB::table('scheduled_actions')->where('status', 'pending')->count())->toBe(0);
});

it('stops when the plugin no longer has the cart (consent withdrawn or expired)', function () {
    wrPushCart();
    $this->liveStatus = 'not_found';

    $this->travel(61)->minutes();
    app(ScheduledActionDispatcher::class)->dispatchDue();

    expect($this->sent)->toBe([])
        ->and(DB::table('recovery_sequences')->value('reason'))->toBe('cart_gone')
        ->and(DB::table('scheduled_actions')->where('status', 'pending')->count())->toBe(0);
});

it('retries later, and never sends, while the plugin cannot be reached', function () {
    wrPushCart();
    $this->liveStatus = 'down';

    $this->travel(61)->minutes();
    app(ScheduledActionDispatcher::class)->dispatchDue();

    $action = DB::table('scheduled_actions')->sole();

    expect($this->sent)->toBe([])
        ->and($action->status)->toBe('pending')
        ->and($action->last_error)->toBe('cart_check_failed')
        ->and(DB::table('recovery_sequences')->value('status'))->toBe('active');
});

it('sends nothing after the customer says stop on WhatsApp, even if the checkout box comes in again', function () {
    wrPushCart();

    app(TenantContext::class)->run($this->team->id, fn () => app(ConsentGate::class)->revoke(PhoneNumber::tryParse('+966551234567'), ConsentSource::OptOutKeyword));
    $this->travel(5)->minutes();
    wrPushCart();

    $this->travel(61)->minutes();
    app(ScheduledActionDispatcher::class)->dispatchDue();

    expect($this->sent)->toBe([])
        ->and(DB::table('recovery_sequences')->value('status'))->toBe('cancelled');
});
