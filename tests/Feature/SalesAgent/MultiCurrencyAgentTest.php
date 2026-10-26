<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\AiGateway\Data\ChatTurn;
use App\Modules\AiGateway\Testing\FakeLlm;
use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\Data\ProductData;
use App\Modules\Commerce\Data\VariantData;
use App\Modules\SalesAgent\AgentControls;
use App\Modules\SalesAgent\AgentMode;
use App\Modules\SalesAgent\SalesAgent;
use App\Modules\SalesAgent\SystemPrompt;
use App\Modules\SalesAgent\Toolbox;
use App\Modules\SalesAgent\Tools\GetProduct;
use App\Modules\SalesAgent\Tools\RunContext;
use App\Shared\Money\Currencies;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Tenancy\WorkspaceLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/** A workspace in $currency with one product, as its store sells it (ADR-012). */
function currencyStore(string $currency, int $priceMinor, ?int $wasMinor = null, array $variants = []): Team
{
    $team = Team::factory()->create(['name' => 'رهف', 'currency' => $currency, 'timezone' => 'Africa/Cairo']);
    $owner = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    app(TenantContext::class)->run($team->id, function () use ($owner, $priceMinor, $wasMinor, $variants) {
        app(CommerceRecorder::class)->recordProduct(new ProductData(
            '3195', 'سلوبيت شتوي', null, null, $priceMinor, 4, 'sale', 'سلوبيت قطن تقيل', 'https://rahaf.test/p/3195', $wasMinor,
            variants: $variants,
        ));
        app(AgentControls::class)->setMode($owner->id, AgentMode::Assisted);
    });

    return $team;
}

function getProduct(Team $team): array
{
    return app(TenantContext::class)->run($team->id, function () {
        $tool = collect(app(Toolbox::class)->forRun(new RunContext('h')))->first(fn ($t) => $t->name() === 'get_product');

        return $tool->handle(['product_id' => '3195']);
    });
}

it('returns exact prices in the workspace currency, with the word the agent writes', function () {
    $team = currencyStore('EGP', 34500, 42000, [new VariantData('3195-6', '6 شهور', 38200, 0), new VariantData('3195-9', '9 شهور', 34500, 3)]);

    $product = getProduct($team);

    expect($product['currency'])->toBe('EGP')
        ->and($product['currency_label'])->toBe('جنيه')
        ->and($product['price'])->toBe('345')
        ->and($product['was_price'])->toBe('420')
        ->and($product['variants'][0])->toMatchArray(['name' => '6 شهور', 'price' => '382', 'in_stock' => false])
        ->and($product['variants'][1]['price'])->toBe('345')
        ->and($product)->not->toHaveKey('price_sar');
});

it('keeps every decimal of a 3-decimal currency and none for a 0-decimal one', function () {
    expect(getProduct(currencyStore('KWD', 12345)))->toMatchArray(['price' => '12.345', 'currency' => 'KWD', 'currency_label' => 'دينار'])
        ->and(getProduct(currencyStore('IQD', 25000)))->toMatchArray(['price' => '25000', 'currency' => 'IQD']);
});

it('keeps Saudi stores in riyals', function () {
    expect(getProduct(currencyStore('SAR', 19900)))->toMatchArray(['price' => '199', 'currency' => 'SAR', 'currency_label' => 'ريال']);
});

it('labels each currency with a properly spelled word, or its symbol', function () {
    expect(GetProduct::label(Currencies::get('LBP')))->toBe('ليرة')
        ->and(GetProduct::label(Currencies::get('USD')))->toBe('دولار')
        ->and(GetProduct::label(Currencies::get('AED')))->toBe('درهم');
});

it('tells the model the workspace currency (prompt v3)', function () {
    $prompt = SystemPrompt::for('رهف', Currencies::get('EGP'));

    expect(SystemPrompt::VERSION)->toBe('v3')
        ->and($prompt)->toContain('"جنيه" (or "EGP" in English)')
        ->and($prompt)->not->toContain('ريال')
        ->and(SystemPrompt::for('عطور', Currencies::get('SAR')))->toContain('"ريال" (or "SAR" in English)');
});

it('drafts a pound price and blocks the same number in riyals', function () {
    $team = currencyStore('EGP', 34500);

    FakeLlm::script([
        FakeLlm::tool('get_product', ['product_id' => '3195']),
        FakeLlm::reply('السلوبيت الشتوي بـ 345 ريال'),
        FakeLlm::tool('get_product', ['product_id' => '3195']),
        FakeLlm::reply('السلوبيت الشتوي بـ 345 جنيه'),
    ]);

    $decision = app(TenantContext::class)->run($team->id, fn () => app(SalesAgent::class)->evaluate([ChatTurn::customer('السلوبيت بكام؟')], 'h1'));

    expect($decision->text)->toBe('السلوبيت الشتوي بـ 345 جنيه')
        ->and($decision->validatorBlocks)->toBe(1);
});

it('hands off a high cart at the dollar default converted to the workspace currency', function () {
    $team = currencyStore('EGP', 34500);
    $threshold = Currencies::get('EGP')->minorFromUsd((float) config('codex.ai.handoff_cart_usd'));

    app(TenantContext::class)->run($team->id, function () use ($team, $threshold) {
        expect(app(AgentControls::class)->handoffCartMinor())->toBe($threshold);

        foreach (['small' => $threshold - 1, 'big' => $threshold] as $hash => $total) {
            DB::table('carts')->insert([
                'tenant_id' => $team->id, 'external_id' => 'cart-'.$hash, 'phone_hash' => $hash, 'total_halalas' => $total,
                'checkout_url' => 'https://rahaf.test/checkout', 'status' => 'abandoned', 'abandoned_at' => now(), 'discovered_by' => 'webhook',
            ]);
        }

        FakeLlm::script([FakeLlm::reply('أهلًا بيك')]);
        expect(app(SalesAgent::class)->evaluate([ChatTurn::customer('الطلب يوصل امتى؟')], 'small')->handoff)->toBeNull();

        FakeLlm::script([]);
        expect(app(SalesAgent::class)->evaluate([ChatTurn::customer('الطلب يوصل امتى؟')], 'big')->handoff?->value)->toBe('high_cart_value');
    });
});

it('keeps the Saudi default at exactly 1,500 riyals', function () {
    $team = currencyStore('SAR', 19900);

    expect(app(TenantContext::class)->run($team->id, fn () => app(AgentControls::class)->handoffCartMinor()))->toBe(150_000);
});

it('reads and sets the cart threshold in major units of the workspace currency', function (string $currency, string $input, int $minor, string $shown) {
    $team = currencyStore($currency, 1000);
    $owner = $team->members()->first();
    $url = "/api/v1/teams/{$team->slug}/ai/settings";

    $this->actingAs($owner)->putJson($url, ['handoff_cart' => $input])->assertOk()
        ->assertJsonPath('data.handoff_cart', $shown);

    expect(DB::table('agent_settings')->where('tenant_id', $team->id)->value('handoff_cart_halalas'))->toBe($minor);
})->with([
    'pounds' => ['EGP', '20000', 2_000_000, '20000.00'],
    'pounds with piasters' => ['EGP', '2500.50', 250_050, '2500.50'],
    'Kuwaiti dinars' => ['KWD', '150.125', 150_125, '150.125'],
    'Iraqi dinars' => ['IQD', '750000', 750_000, '750000'],
    'riyals' => ['SAR', '1500', 150_000, '1500.00'],
]);

it('refuses more decimals than the currency has, and a zero threshold', function (string $currency, mixed $input) {
    $team = currencyStore($currency, 1000);

    $this->actingAs($team->members()->first())->putJson("/api/v1/teams/{$team->slug}/ai/settings", ['handoff_cart' => $input])
        ->assertUnprocessable()->assertJsonValidationErrors('handoff_cart');
})->with([
    'piasters past 2' => ['EGP', '100.555'],
    'fils past 3' => ['KWD', '1.0001'],
    'no decimals in IQD' => ['IQD', '100.5'],
    'zero' => ['EGP', '0'],
    'negative' => ['EGP', '-5'],
    'text' => ['EGP', 'كتير'],
]);

it('clears the stored Saudi default threshold, so a workspace that later takes pounds reads the converted default', function () {
    $sar = currencyStore('SAR', 19900);
    $egp = currencyStore('EGP', 34500);
    DB::table('agent_settings')->whereIn('tenant_id', [$sar->id, $egp->id])->update(['handoff_cart_halalas' => 150_000]);

    (require database_path('migrations/2026_10_28_000003_reset_default_handoff_cart_threshold.php'))->up();

    expect(DB::table('agent_settings')->where('tenant_id', $sar->id)->value('handoff_cart_halalas'))->toBeNull()
        // Already in another currency: the merchant's own 1,500 pounds stays.
        ->and(DB::table('agent_settings')->where('tenant_id', $egp->id)->value('handoff_cart_halalas'))->toBe(150_000)
        ->and(app(TenantContext::class)->run($sar->id, fn () => app(AgentControls::class)->handoffCartMinor()))->toBe(150_000);

    $sar->update(['currency' => 'EGP']);
    app(WorkspaceLocale::class)->forget($sar->id);

    expect(app(TenantContext::class)->run($sar->id, fn () => app(AgentControls::class)->handoffCartMinor()))
        ->toBe(Currencies::get('EGP')->minorFromUsd((float) config('codex.ai.handoff_cart_usd')));
});
