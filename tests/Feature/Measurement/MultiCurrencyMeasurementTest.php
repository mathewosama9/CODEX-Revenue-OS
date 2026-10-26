<?php

use App\Models\Team;
use App\Modules\AiGateway\AiGateway;
use App\Modules\Billing\UsageMeter;
use App\Modules\Commerce\CartStatus;
use App\Modules\Commerce\Data\CartSnapshot;
use App\Modules\Compliance\QuietHours;
use App\Modules\Measurement\CostLedger;
use App\Modules\Measurement\ExperimentAssigner;
use App\Modules\Measurement\ProfitReport;
use App\Modules\Measurement\ReportPeriod;
use App\Modules\Messaging\Events\MessageDelivered;
use App\Modules\Messaging\MessageStatus;
use App\Modules\Messaging\Models\Message;
use App\Modules\Recovery\DiscountPolicy;
use App\Modules\Recovery\Models\RecoverySettings;
use App\Shared\Money\Currencies;
use App\Shared\Phone\CallingCode;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/*
| ADR-012: the ledger, the profit report, the discount budget and every local
| day follow the workspace's currency and timezone. SAR / Riyadh stay as before.
*/

function workspace(string $currency = 'SAR', string $timezone = 'Asia/Riyadh'): Team
{
    $team = Team::factory()->create(['currency' => $currency, 'timezone' => $timezone]);
    DB::table('category_margins')->insert(['tenant_id' => $team->id, 'category_external_id' => null, 'margin_bps' => 4000, 'effective_from' => '2026-01-01']);

    return $team;
}

function asWorkspace(Team $team, Closure $callback): mixed
{
    return app(TenantContext::class)->run($team->id, $callback);
}

/**
 * Two treated buyers (one order of $orderMinor each) and two control non-buyers.
 *
 * @return list<string> the buyers' hashes
 */
function seedPilot(Team $team, int $orderMinor, string $currency): array
{
    $experimentId = asWorkspace($team, fn () => app(ExperimentAssigner::class)->experiment()->id);
    $buyers = [];

    foreach (['b1', 'b2', 'n1', 'n2'] as $i => $name) {
        $hash = hash('sha256', $team->id.$name);
        $treated = str_starts_with($name, 'b');
        DB::table('exposures')->insert(['tenant_id' => $team->id, 'experiment_id' => $experimentId, 'subject_hash' => $hash, 'arm' => $treated ? 'treatment' : 'control', 'cart_id' => $i + 1, 'cart_total_halalas' => $orderMinor, 'eligible_at' => '2026-11-01 10:00']);

        if ($treated) {
            DB::table('orders')->insert(['tenant_id' => $team->id, 'external_id' => "o{$i}", 'phone_hash' => $hash, 'total_halalas' => $orderMinor, 'placed_at' => '2026-11-02', 'currency' => $currency]);
            $buyers[] = $hash;
        }
    }

    return $buyers;
}

function pilotReport(Team $team): array
{
    return asWorkspace($team, function () {
        $period = ReportPeriod::days('2026-11-01', '2026-11-30');

        return app(ProfitReport::class)->compute($period->start, $period->end);
    });
}

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-12-31 12:00'));
});

it('maps E.164 numbers to their country', function () {
    expect(CallingCode::country(PhoneNumber::tryParse('0512345678')))->toBe('SA')
        ->and(CallingCode::country('+201001234567'))->toBe('EG')
        ->and(CallingCode::country('+96550012345'))->toBe('KW')
        ->and(CallingCode::country('+212612345678'))->toBe('MA')
        ->and(CallingCode::country('+14155550100'))->toBeNull()
        ->and(CallingCode::country(null))->toBeNull();
});

it('keeps a Saudi message at the sourced rate in a SAR workspace, and the recovery cost at 22 halalas', function () {
    $team = workspace();

    asWorkspace($team, function () {
        $ledger = app(CostLedger::class);
        $ledger->recordMessageDelivered('message:1', 'h', 'marketing', true, CarbonImmutable::now(), 'SA');

        // 0.0576 USD × 3.75 = 0.216 SAR; StartRecovery rounds it up to the old 22 halalas.
        expect($ledger->messageCostMicros(PhoneNumber::tryParse('0512345678')))->toBe(216_000)
            ->and(intdiv(216_000 + 10_000 - 1, 10_000))->toBe(22);
    });

    $entry = DB::table('cost_ledger_entries')->first();
    expect($entry->amount_micros)->toBe(216_000)
        ->and(json_decode($entry->meta, true))->toEqual(['category' => 'marketing', 'country' => 'SA']);
});

it('books a message to an Egyptian number in pounds, marked estimated', function () {
    $team = workspace('EGP', 'Africa/Cairo');

    asWorkspace($team, function () {
        $message = Message::create([
            'direction' => Message::OUTBOUND, 'subject_hash' => hash('sha256', 'eg'), 'status' => MessageStatus::Delivered,
            'category' => 'marketing', 'delivered_at' => CarbonImmutable::now(), 'context' => ['recipient_country' => 'EG'],
        ]);

        MessageDelivered::dispatch($message->tenant_id, $message->id, 'marketing', true);
    });

    $entry = DB::table('cost_ledger_entries')->first();

    // No sourced Egyptian rate yet: the highest sourced rate (0.0576 USD) × 48.5 EGP/USD.
    expect($entry->amount_micros)->toBe(Currencies::get('EGP')->microsFromUsd(0.0576))
        ->and($entry->amount_micros)->toBe(2_793_600)
        ->and(json_decode($entry->meta, true))->toEqual(['category' => 'marketing', 'country' => 'EG', 'estimated' => true]);
});

it('reports an EGP workspace in piasters, costs converted and nothing mixed', function () {
    $team = workspace('EGP', 'Africa/Cairo');
    $buyers = seedPilot($team, 18000, 'EGP');

    asWorkspace($team, function () use ($buyers) {
        $ledger = app(CostLedger::class);

        foreach ($buyers as $i => $hash) {
            $ledger->recordMessageDelivered("message:{$i}", $hash, 'marketing', true, CarbonImmutable::parse('2026-11-01 11:00'), 'EG');
        }
    });

    $report = pilotReport($team);

    // 2 × 2,793,600 micros = 558.72 piasters; each buyer: 180 EGP × 40% − 27.936 EGP.
    expect($report['currency'])->toBe(['code' => 'EGP', 'exponent' => 2, 'symbol' => 'ج.م'])
        ->and($report['costs_halalas']['meta_message'])->toBe(559)
        ->and($report['costs_estimated'])->toBeTrue()
        ->and($report['incremental_profit_halalas']['per_customer']['low'])->toEqual(round(7200 - 279.36, 2))
        ->and($report['period'])->toBe(['from' => '2026-11-01', 'to' => '2026-11-30']);
});

it('reports a KWD workspace in fils (exponent 3)', function () {
    $team = workspace('KWD', 'Asia/Kuwait');
    $buyers = seedPilot($team, 20_000, 'KWD');

    asWorkspace($team, function () use ($buyers) {
        $ledger = app(CostLedger::class);
        $ledger->recordDiscount('order:1', $buyers[0], 2_000, CarbonImmutable::parse('2026-11-02'));
        $ledger->recordLlm('ai:1', $buyers[0], 1_000_000, CarbonImmutable::parse('2026-11-01 12:00'), 'claude-haiku-5-5');
        $ledger->recordMessageDelivered('message:1', $buyers[1], 'marketing', true, CarbonImmutable::parse('2026-11-01 11:00'), 'KW');
    });

    $report = pilotReport($team);

    // 1 fils = 1,000 micros. $1 of AI = 0.307 KWD = 307 fils; 0.0576 USD × 0.307 = 17.68 fils.
    expect($report['currency'])->toBe(['code' => 'KWD', 'exponent' => 3, 'symbol' => 'د.ك'])
        ->and($report['costs_halalas']['discount'])->toBe(2_000)
        ->and($report['costs_halalas']['llm'])->toBe(307)
        ->and($report['costs_halalas']['meta_message'])->toBe(18)
        ->and($report['costs_estimated'])->toBeTrue();
});

it('keeps a SAR report unestimated, with the 500-halala detectable effect', function () {
    $team = workspace();
    seedPilot($team, 20000, 'SAR');

    $report = pilotReport($team);

    expect($report['currency'])->toBe(['code' => 'SAR', 'exponent' => 2, 'symbol' => 'ر.س'])
        ->and($report['costs_estimated'])->toBeFalse()
        ->and(Currencies::get('SAR')->minorFromUsd((float) config('codex.measurement.mde_usd_per_subject')))->toBe(500)
        ->and(Currencies::get('SAR')->minorFromUsd((float) config('codex.ai.handoff_cart_usd')))->toBe(150_000);
});

function cartOf(Team $team, int $totalMinor, string $currency): CartSnapshot
{
    return new CartSnapshot(1, $team->id, 'c1', 'h', null, $totalMinor, $currency, null, CartStatus::Abandoned, null, []);
}

it('holds the KWD discount budget in fils', function () {
    $team = workspace('KWD', 'Asia/Kuwait');

    asWorkspace($team, function () use ($team) {
        $settings = RecoverySettings::current();
        $settings->monthly_discount_budget_halalas = 10_000; // 10 KWD
        $cart = cartOf($team, 20_000, 'KWD'); // 10% = 2 KWD

        app(CostLedger::class)->recordDiscount('order:1', 'h', 8_000, CarbonImmutable::now());
        expect(app(DiscountPolicy::class)->coversBudget($settings, $cart, 10))->toBeTrue();

        app(CostLedger::class)->recordDiscount('order:2', 'h', 1_000, CarbonImmutable::now());
        expect(app(DiscountPolicy::class)->coversBudget($settings, $cart, 10))->toBeFalse();
    });
});

it('starts the budget month at local midnight in Cairo', function () {
    // 30 Nov 22:00 Cairo = 23:00 Riyadh; "now" is 1 Dec 00:30 Riyadh = 30 Nov 23:30 Cairo.
    $this->travelTo(CarbonImmutable::parse('2026-12-01 00:30', 'Asia/Riyadh'));
    $spentAt = CarbonImmutable::parse('2026-11-30 22:00', 'Africa/Cairo');

    $covers = function (Team $team) use ($spentAt) {
        return asWorkspace($team, function () use ($team, $spentAt) {
            $settings = RecoverySettings::current();
            $settings->monthly_discount_budget_halalas = 10_000;
            app(CostLedger::class)->recordDiscount('order:1', 'h', 9_000, $spentAt);

            return app(DiscountPolicy::class)->coversBudget($settings, cartOf($team, 20_000, $team->currency), 10);
        });
    };

    // Still November in Cairo: the 90 spent count. Already December in Riyadh: they do not.
    expect($covers(workspace('EGP', 'Africa/Cairo')))->toBeFalse()
        ->and($covers(workspace()))->toBeTrue();
});

it('keeps quiet hours on Cairo time in a Cairo workspace', function () {
    $at = CarbonImmutable::parse('2026-11-10 21:30', 'Africa/Cairo'); // 22:30 in Riyadh

    $cairo = workspace('EGP', 'Africa/Cairo');
    $riyadh = workspace();

    expect(asWorkspace($cairo, fn () => app(QuietHours::class)->isQuiet($at)))->toBeFalse()
        ->and(asWorkspace($riyadh, fn () => app(QuietHours::class)->isQuiet($at)))->toBeTrue()
        ->and(asWorkspace($cairo, fn () => app(QuietHours::class)->nextAllowedAt($at->addHour())->setTimezone('Africa/Cairo')->format('Y-m-d H:i')))->toBe('2026-11-11 09:00');

    // Ramadan windows too (01:00–12:00 local): 11:30 Cairo is 12:30 Riyadh.
    $ramadan = CarbonImmutable::parse('2027-02-15 11:30', 'Africa/Cairo');
    expect(asWorkspace($cairo, fn () => app(QuietHours::class)->isQuiet($ramadan)))->toBeTrue()
        ->and(asWorkspace($riyadh, fn () => app(QuietHours::class)->isQuiet($ramadan)))->toBeFalse();
});

it('cuts report days, usage days and the AI day on the workspace clock', function () {
    $cairo = workspace('EGP', 'Africa/Cairo');
    $this->travelTo(CarbonImmutable::parse('2026-11-30 21:30', 'UTC')); // 23:30 Cairo, 00:30 Riyadh next day

    asWorkspace($cairo, function () use ($cairo) {
        expect(ReportPeriod::days('2026-11-01', '2026-11-30')->start->toIso8601String())->toBe('2026-10-31T22:00:00+00:00');

        app(UsageMeter::class)->increment(UsageMeter::ELIGIBLE_CARTS);

        DB::table('ai_calls')->insert([
            'tenant_id' => $cairo->id, 'purpose' => 'sales_agent', 'provider' => 'anthropic', 'model' => 'claude-haiku-5-5',
            'status' => 'ok', 'cost_usd_micros' => 1_000, 'created_at' => CarbonImmutable::parse('2026-11-30 00:30', 'Africa/Cairo')->utc(),
        ]);

        // Today in Cairo started 23 hours ago, so the 00:30 call is today's.
        expect(app(AiGateway::class)->spentTodayUsdMicros())->toBe(1_000);
    });

    expect(DB::table('usage_daily')->value('day'))->toStartWith('2026-11-30');
});
