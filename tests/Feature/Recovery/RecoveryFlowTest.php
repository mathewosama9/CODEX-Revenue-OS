<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\Data\CartData;
use App\Modules\Commerce\Data\CustomerData;
use App\Modules\Commerce\Data\LineItemData;
use App\Modules\Commerce\Data\OrderData;
use App\Modules\Commerce\DiscoveredBy;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Modules\Measurement\ExperimentAssigner;
use App\Modules\Recovery\ScheduledActionDispatcher;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    // 14:00 Riyadh on a normal day: outside quiet hours.
    $this->travelTo(CarbonImmutable::parse('2026-11-10 14:00', 'Asia/Riyadh'));

    $this->team = Team::factory()->create(['name' => 'عطور الريم']);
    $this->owner = User::factory()->create();
    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->phone = PhoneNumber::tryParse('0512345678');
    $t = $this->team->id;

    DB::table('store_connections')->insert(['tenant_id' => $t, 'provider' => 'salla', 'external_store_id' => 'm1', 'status' => 'active', 'access_token' => encrypt('x', false)]);
    $account = DB::table('whatsapp_accounts')->insertGetId(['tenant_id' => $t, 'waba_id' => 'w1', 'access_token' => encrypt('t', false), 'status' => 'active']);
    DB::table('whatsapp_numbers')->insert(['tenant_id' => $t, 'whatsapp_account_id' => $account, 'phone_number_id' => 'pn1', 'quality_rating' => 'GREEN', 'status' => 'active']);

    foreach (['cart_recovery_1' => 'r1', 'cart_recovery_2' => 'r2', 'cart_recovery_2_discount' => 'r2d'] as $purpose => $name) {
        DB::table('message_templates')->insert(['tenant_id' => $t, 'whatsapp_account_id' => $account, 'name' => $name, 'language' => 'ar', 'category' => 'MARKETING', 'status' => 'APPROVED', 'purpose' => $purpose, 'components' => '[]']);
    }

    DB::table('category_margins')->insert(['tenant_id' => $t, 'category_external_id' => null, 'margin_bps' => 4000, 'effective_from' => now()->subYear()]);
    DB::table('recovery_settings')->insert(['tenant_id' => $t, 'enabled' => true]);

    tenant(fn () => app(ConsentGate::class)->grant($this->phone, ConsentSource::CheckoutOptIn, ['text_version' => 'v1']));
    setHoldout(0);

    $this->sent = [];
    Http::fake(function (Request $request) {
        if (str_contains($request->url(), 'graph.facebook.com') && ($this->metaError ?? null) !== null) {
            return Http::response(['error' => ['code' => $this->metaError, 'message' => 'error']], 400);
        }

        if (str_contains($request->url(), 'graph.facebook.com')) {
            $this->sent[] = $request->data();

            return Http::response(['messages' => [['id' => 'wamid.'.count($this->sent)]]]);
        }

        if (($this->sallaStatus ?? null) !== null) {
            return Http::response(['error' => 'unavailable'], $this->sallaStatus);
        }

        // Salla live check: the cart is still abandoned unless a test says otherwise.
        return Http::response(['data' => ['id' => 555, 'status' => $this->cartStatus ?? 'abandoned']]);
    });
});

function tenant(Closure $callback): mixed
{
    return app(TenantContext::class)->run(test()->team->id, $callback);
}

function setHoldout(int $bps): void
{
    tenant(fn () => app(ExperimentAssigner::class)->experiment());
    DB::table('experiments')->update(['holdout_bps' => $bps]);
}

function abandonCart(int $totalSar = 420, string $id = '555', ?PhoneNumber $phone = null, DiscoveredBy $by = DiscoveredBy::Webhook): int
{
    tenant(fn () => app(CommerceRecorder::class)->recordAbandonedCart(new CartData(
        externalId: $id,
        customer: new CustomerData('c-'.$id, $phone ?? test()->phone),
        totalHalalas: $totalSar * 100,
        currency: 'SAR',
        checkoutUrl: "https://store.sa/checkout/{$id}",
        items: [new LineItemData('p1', 'عود', 1, $totalSar * 100)],
        abandonedAt: CarbonImmutable::now(),
    ), $by));

    return (int) DB::table('carts')->where('external_id', $id)->value('id');
}

function runDue(): int
{
    return app(ScheduledActionDispatcher::class)->dispatchDue();
}

it('sends message 1 after an hour, schedules message 2, and records the exposure', function () {
    $cartId = abandonCart();

    expect(DB::table('recovery_sequences')->value('status'))->toBe('active')
        ->and(DB::table('exposures')->where('cart_id', $cartId)->value('arm'))->toBe('treatment');

    expect(runDue())->toBe(0);

    $this->travel(61)->minutes();
    expect(runDue())->toBe(1);

    expect($this->sent)->toHaveCount(1)
        ->and($this->sent[0]['template']['name'])->toBe('r1')
        ->and($this->sent[0]['template']['components'][0]['parameters'][1]['text'])->toBe('https://store.sa/checkout/555');

    $step2 = DB::table('scheduled_actions')->where('step', 2)->first();
    expect($step2->status)->toBe('pending');

    $this->travel(24)->hours();
    runDue();

    expect($this->sent)->toHaveCount(2)
        ->and($this->sent[1]['template']['name'])->toBe('r2')
        ->and(DB::table('recovery_sequences')->value('status'))->toBe('completed');
});

it('never sends to the control group, but logs the same exposure', function () {
    setHoldout(10000);

    $cartId = abandonCart();
    $this->travel(2)->hours();
    runDue();

    expect($this->sent)->toBe([])
        ->and(DB::table('recovery_sequences')->value('status'))->toBe('holdout')
        ->and(DB::table('exposures')->where('cart_id', $cartId)->value('arm'))->toBe('control');
});

it('does not start without consent', function () {
    abandonCart(phone: PhoneNumber::tryParse('0599999999'));

    expect(DB::table('recovery_sequences')->first())
        ->status->toBe('ineligible')
        ->reason->toBe('no_consent');
    expect(DB::table('exposures')->count())->toBe(0);
});

it('never starts from backfilled history', function () {
    abandonCart(by: DiscoveredBy::Backfill);

    expect(DB::table('recovery_sequences')->count())->toBe(0);
});

it('suppresses carts that are not worth the message cost, in both arms alike', function () {
    abandonCart(totalSar: 5);

    expect(DB::table('recovery_sequences')->value('reason'))->toBe('not_worth_cost')
        ->and(DB::table('exposures')->count())->toBe(0);
});

it('caps one sequence per customer per 7 days', function () {
    abandonCart(id: '1');
    abandonCart(id: '2');

    expect(DB::table('recovery_sequences')->where('reason', 'frequency_cap')->count())->toBe(1);
});

it('moves a send inside quiet hours to 09:00 Riyadh', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-10 21:30', 'Asia/Riyadh'));

    abandonCart();

    $runAt = CarbonImmutable::parse(DB::table('scheduled_actions')->value('run_at'))->setTimezone('Asia/Riyadh');
    expect($runAt->format('Y-m-d H:i'))->toBe('2026-11-11 09:00');
});

it('cancels everything the moment the cart is bought', function () {
    abandonCart();
    tenant(fn () => app(CommerceRecorder::class)->markCartPurchased('555'));

    $this->travel(2)->hours();
    runDue();

    expect($this->sent)->toBe([])
        ->and(DB::table('scheduled_actions')->value('status'))->toBe('cancelled')
        ->and(DB::table('recovery_sequences')->value('reason'))->toBe('purchased');
});

it('cancels when an order arrives from the same customer', function () {
    abandonCart();
    tenant(fn () => app(CommerceRecorder::class)->recordOrder(new OrderData(
        externalId: 'o1', customer: new CustomerData('c-555', $this->phone), totalHalalas: 42000, discountHalalas: 0,
        shippingHalalas: 0, currency: 'SAR', status: 'new', paymentMethod: 'card', couponCode: null,
        placedAt: CarbonImmutable::now(), items: [],
    )));

    expect(DB::table('recovery_sequences')->value('reason'))->toBe('ordered');
});

it('does not send when Salla says the cart was bought meanwhile', function () {
    abandonCart();
    $this->cartStatus = 'purchased';

    $this->travel(2)->hours();
    runDue();

    expect($this->sent)->toBe([])
        ->and(DB::table('recovery_sequences')->value('reason'))->toBe('purchased');
});

it('cancels pending sends on opt-out', function () {
    abandonCart();
    tenant(fn () => app(ConsentGate::class)->revoke($this->phone, ConsentSource::OptOutKeyword));

    $this->travel(2)->hours();
    runDue();

    expect($this->sent)->toBe([])
        ->and(DB::table('recovery_sequences')->value('reason'))->toBe('opt_out');
});

it('claims each due step once, even with two schedulers', function () {
    abandonCart();
    $this->travel(2)->hours();

    expect(runDue() + runDue())->toBe(1)
        ->and($this->sent)->toHaveCount(1);
});

it('never puts a discount in message 1, and asks the owner for an existing customer', function () {
    DB::table('recovery_settings')->update(['discount_enabled' => true, 'discount_percent' => 10, 'discount_code' => 'BACK10', 'discount_cap_percent' => 10, 'monthly_discount_budget_halalas' => 100_000]);
    tenant(fn () => app(CommerceRecorder::class)->recordOrder(new OrderData(
        externalId: 'old', customer: new CustomerData('c-555', $this->phone), totalHalalas: 10000, discountHalalas: 0,
        shippingHalalas: 0, currency: 'SAR', status: 'delivered', paymentMethod: 'card', couponCode: null,
        placedAt: CarbonImmutable::now()->subMonths(2), items: [],
    )));
    $this->travel(8)->days();

    abandonCart();
    $this->travel(61)->minutes();
    runDue();

    expect($this->sent[0]['template']['name'])->toBe('r1');

    $this->travel(24)->hours();
    runDue();

    expect($this->sent)->toHaveCount(1)
        ->and(DB::table('discount_approvals')->value('reason'))->toBe('existing_customer')
        ->and(DB::table('scheduled_actions')->where('step', 2)->value('status'))->toBe('awaiting_approval');

    $approvalId = DB::table('discount_approvals')->value('id');
    $this->actingAs($this->owner)
        ->postJson("/api/v1/teams/{$this->team->slug}/recovery/approvals/{$approvalId}", ['decision' => 'approve'])
        ->assertOk();

    runDue();

    expect($this->sent)->toHaveCount(2)
        ->and($this->sent[1]['template']['name'])->toBe('r2d')
        ->and($this->sent[1]['template']['components'][0]['parameters'][1]['text'])->toBe('BACK10');
});

it('sends without a discount when the approval expires', function () {
    DB::table('recovery_settings')->update(['discount_enabled' => true, 'discount_percent' => 20, 'discount_code' => 'BIG20', 'discount_cap_percent' => 10, 'approval_window_minutes' => 60, 'monthly_discount_budget_halalas' => 100_000]);

    abandonCart();
    $this->travel(61)->minutes();
    runDue();
    $this->travel(24)->hours();
    runDue();

    expect(DB::table('discount_approvals')->value('reason'))->toBe('above_cap');

    $this->travel(61)->minutes();
    runDue();

    expect($this->sent[1]['template']['name'])->toBe('r2')
        ->and(DB::table('discount_approvals')->value('status'))->toBe('expired');
});

it('refuses a discount that breaks the margin floor', function () {
    DB::table('recovery_settings')->update(['discount_enabled' => true, 'discount_percent' => 35, 'discount_code' => 'X35', 'discount_cap_percent' => 50, 'margin_floor_bps' => 1000, 'monthly_discount_budget_halalas' => 100_000]);

    abandonCart();
    $this->travel(61)->minutes();
    runDue();
    $this->travel(24)->hours();
    runDue();

    expect($this->sent[1]['template']['name'])->toBe('r2')
        ->and(DB::table('discount_approvals')->count())->toBe(0);
});

it('waits 24 hours after a per-user limit error', function () {
    $this->metaError = 131049;

    abandonCart();
    $this->travel(61)->minutes();
    runDue();

    $action = DB::table('scheduled_actions')->first();
    expect($action->status)->toBe('pending')
        ->and($action->last_error)->toBe('per_user_limit_131049')
        ->and(now()->diffInHours(CarbonImmutable::parse($action->run_at)))->toBeGreaterThanOrEqual(23);
});

it('cannot be enabled until the go-live checklist is green', function () {
    DB::table('recovery_settings')->update(['enabled' => false]);
    setHoldout(1000); // the checklist refuses a 0% holdout, by design
    DB::table('whatsapp_numbers')->update(['quality_rating' => 'RED']);

    $this->actingAs($this->owner)
        ->postJson("/api/v1/teams/{$this->team->slug}/recovery/enable")
        ->assertUnprocessable();

    DB::table('whatsapp_numbers')->update(['quality_rating' => 'GREEN']);

    $this->actingAs($this->owner)
        ->postJson("/api/v1/teams/{$this->team->slug}/recovery/enable")
        ->assertOk()
        ->assertJsonPath('data.settings.enabled', true);
});

it('pauses with the kill switch and keeps the cancelled steps', function () {
    abandonCart();

    $this->actingAs($this->owner)->postJson("/api/v1/teams/{$this->team->slug}/recovery/pause")->assertOk();

    expect(DB::table('scheduled_actions')->value('status'))->toBe('cancelled')
        ->and(DB::table('scheduled_actions')->count())->toBe(1);
});

// --- P1/P2 review fixes (docs/reviews/2026-10-25-p1-p2-issues.md) ---

it('really sends the retry after a per-user limit error (B2)', function () {
    $this->metaError = 131049;

    abandonCart();
    $this->travel(61)->minutes();
    runDue();

    expect($this->sent)->toBe([]);

    $this->metaError = null;
    $this->travel(25)->hours();
    runDue();

    expect($this->sent)->toHaveCount(1)
        ->and($this->sent[0]['template']['name'])->toBe('r1')
        ->and(DB::table('messages')->where('status', 'accepted')->count())->toBe(1)
        ->and(DB::table('scheduled_actions')->where('step', 1)->value('status'))->toBe('done');
});

it('caps treated customers whose sequence ended, like the control arm (B3)', function () {
    abandonCart(id: '1');
    $this->cartStatus = 'purchased';
    $this->travel(61)->minutes();
    runDue();

    expect(DB::table('recovery_sequences')->value('status'))->toBe('cancelled');

    $this->cartStatus = 'abandoned';
    abandonCart(id: '2');

    expect(DB::table('recovery_sequences')->where('reason', 'frequency_cap')->count())->toBe(1);
});

it('retries when Salla is unreachable instead of calling it a purchase (B4)', function () {
    abandonCart();
    $this->sallaStatus = 429;
    $this->travel(61)->minutes();
    runDue();

    $action = DB::table('scheduled_actions')->first();

    expect($this->sent)->toBe([])
        ->and($action->status)->toBe('pending')
        ->and($action->last_error)->toBe('cart_check_failed')
        ->and(DB::table('recovery_sequences')->value('status'))->toBe('active')
        ->and(DB::table('carts')->value('status'))->toBe('abandoned');

    $this->sallaStatus = null;
    $this->travel(16)->minutes();
    runDue();

    expect($this->sent)->toHaveCount(1);
});

it('treats the monthly discount budget as a hard cap, reserved at send time (B5)', function () {
    // 10% of a 420 SAR cart = 42 SAR; a 50 SAR budget covers one such discount, not two.
    DB::table('recovery_settings')->update(['discount_enabled' => true, 'discount_percent' => 10, 'discount_code' => 'BACK10', 'discount_cap_percent' => 10, 'monthly_discount_budget_halalas' => 5_000]);
    $second = PhoneNumber::tryParse('0598765432');
    tenant(fn () => app(ConsentGate::class)->grant($second, ConsentSource::CheckoutOptIn, ['text_version' => 'v1']));

    abandonCart(id: '1');
    abandonCart(id: '2', phone: $second);
    $this->travel(61)->minutes();
    runDue();
    $this->travel(24)->hours();
    runDue();

    $step2 = collect($this->sent)->slice(2)->pluck('template.name')->sort()->values()->all();

    expect($step2)->toBe(['r2', 'r2d'])
        ->and(DB::table('scheduled_actions')->where('step', 2)->get()->sum(fn ($action) => json_decode($action->result, true)['discount_reserved_halalas'] ?? 0))->toBe(4_200);
});

it('sends no discount at all without a budget (B5)', function () {
    DB::table('recovery_settings')->update(['discount_enabled' => true, 'discount_percent' => 10, 'discount_code' => 'BACK10', 'discount_cap_percent' => 10, 'monthly_discount_budget_halalas' => 0]);

    abandonCart();
    $this->travel(61)->minutes();
    runDue();
    $this->travel(24)->hours();
    runDue();

    expect($this->sent[1]['template']['name'])->toBe('r2');
});

it('refuses a discount that is on but cannot go out (B6)', function () {
    $this->actingAs($this->owner)
        ->putJson("/api/v1/teams/{$this->team->slug}/recovery", ['discount_enabled' => true, 'discount_percent' => 10, 'discount_code' => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['discount_code', 'monthly_discount_budget_halalas']);

    $this->actingAs($this->owner)
        ->putJson("/api/v1/teams/{$this->team->slug}/recovery", ['discount_enabled' => true, 'discount_percent' => 10, 'discount_code' => 'BACK10', 'monthly_discount_budget_halalas' => 100_000])
        ->assertOk();
});

it('hides and refuses approvals whose cart no longer needs one (H11)', function () {
    DB::table('recovery_settings')->update(['discount_enabled' => true, 'discount_percent' => 20, 'discount_code' => 'BIG20', 'discount_cap_percent' => 10, 'monthly_discount_budget_halalas' => 100_000]);

    abandonCart();
    $this->travel(61)->minutes();
    runDue();
    $this->travel(24)->hours();
    runDue();

    $approvalId = DB::table('discount_approvals')->value('id');
    $base = "/api/v1/teams/{$this->team->slug}/recovery/approvals";

    // The customer replies: the sequence ends, the approval is moot.
    DB::table('scheduled_actions')->where('step', 2)->update(['status' => 'cancelled']);

    $this->actingAs($this->owner)->getJson($base)->assertOk()->assertJsonCount(0, 'data');
    $this->actingAs($this->owner)->postJson("{$base}/{$approvalId}", ['decision' => 'approve'])->assertStatus(409);

    expect(DB::table('discount_approvals')->value('status'))->toBe('pending');
});
