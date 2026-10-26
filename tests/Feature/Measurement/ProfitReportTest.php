<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Measurement\ExperimentAssigner;
use App\Modules\Measurement\ProfitReport;
use App\Modules\Measurement\SampleRatioCheck;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-12-31 12:00'));
    $this->team = Team::factory()->create();
    DB::table('category_margins')->insert(['tenant_id' => $this->team->id, 'category_external_id' => null, 'margin_bps' => 4000, 'effective_from' => '2026-01-01']);
    $this->experimentId = inTenant(fn () => app(ExperimentAssigner::class)->experiment()->id);
});

function inTenant(Closure $callback): mixed
{
    return app(TenantContext::class)->run(test()->team->id, $callback);
}

/**
 * Synthetic experiment with a known truth: each customer converts with the
 * arm's probability and spends 200 SAR; treated customers cost 2 messages.
 */
function seedExperiment(int $treated, int $control, float $treatedRate, float $controlRate, int $seed = 7): void
{
    // A private generator: the global mt_rand is reseeded at random whenever an
    // earlier test's Faker instance is garbage-collected (Faker\Generator::__destruct),
    // which made this "known truth" random and the test fail about 1 run in 20.
    $random = new Randomizer(new Mt19937($seed));
    $t = test()->team->id;
    $exposures = $orders = $ledger = [];
    $eligibleAt = CarbonImmutable::parse('2026-11-01 10:00');

    foreach (['treatment' => [$treated, $treatedRate], 'control' => [$control, $controlRate]] as $arm => [$count, $rate]) {
        for ($i = 0; $i < $count; $i++) {
            $hash = hash('sha256', "{$arm}-{$i}");
            $exposures[] = ['tenant_id' => $t, 'experiment_id' => test()->experimentId, 'subject_hash' => $hash, 'arm' => $arm, 'cart_id' => count($exposures) + 1, 'cart_total_halalas' => 20000, 'eligible_at' => $eligibleAt];

            if ($random->nextFloat() < $rate) {
                $orders[] = ['tenant_id' => $t, 'external_id' => "o-{$arm}-{$i}", 'phone_hash' => $hash, 'total_halalas' => 20000, 'placed_at' => $eligibleAt->addDays(2), 'currency' => 'SAR'];
            }

            if ($arm === 'treatment') {
                foreach ([1, 2] as $step) {
                    $ledger[] = ['tenant_id' => $t, 'kind' => 'meta_message', 'amount_micros' => 216_000, 'reference' => "m-{$i}-{$step}", 'subject_hash' => $hash, 'meta' => '{}', 'occurred_at' => $eligibleAt->addHour()];
                }
            }
        }
    }

    foreach (array_chunk($exposures, 1000) as $chunk) {
        DB::table('exposures')->insert($chunk);
    }
    foreach (array_chunk($orders, 1000) as $chunk) {
        DB::table('orders')->insert($chunk);
    }
    foreach (array_chunk($ledger, 1000) as $chunk) {
        DB::table('cost_ledger_entries')->insert($chunk);
    }
}

function profitReport(): array
{
    return inTenant(fn () => app(ProfitReport::class)->compute(CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-12-01')));
}

it('recovers a known effect inside its interval', function () {
    seedExperiment(treated: 4000, control: 4000, treatedRate: 0.08, controlRate: 0.05);

    $report = profitReport();

    // Truth per treated customer: (0.08 − 0.05) × 200 SAR × 40% − 2 × 0.216 SAR = 1.968 SAR = 196.8 halalas.
    $truth = 196.8;
    $interval = $report['incremental_profit_halalas']['per_customer'];

    expect($report['sample'])->toBe(['treatment' => 4000, 'control' => 4000, 'not_yet_matured' => 0])
        ->and($interval['low'])->toBeLessThan($truth)
        ->and($interval['high'])->toBeGreaterThan($truth)
        ->and($report['incremental_profit_halalas']['win_probability'])->toBeGreaterThan(0.95)
        ->and($report['verdict'])->toBe('helpful');
});

it('stays near 50% win probability when there is no effect', function () {
    seedExperiment(treated: 3000, control: 3000, treatedRate: 0.06, controlRate: 0.06, seed: 11);

    $revenue = profitReport()['incremental_revenue_halalas'];

    expect($revenue['win_probability'])->toBeGreaterThan(0.05)->toBeLessThan(0.95)
        ->and($revenue['verdict'])->toBe('inconclusive');
});

it('says honestly when a small pilot is inconclusive, and how many more customers it needs', function () {
    seedExperiment(treated: 360, control: 40, treatedRate: 0.07, controlRate: 0.05);

    $report = profitReport();

    expect($report['verdict'])->toBe('inconclusive')
        ->and($report['additional_customers_needed'])->toBeGreaterThan(0)
        ->and($report['incremental_profit_halalas']['low'])->toBeLessThan(0)
        ->and($report['incremental_profit_halalas']['high'])->toBeGreaterThan(0);
});

it('separates attributed revenue from incremental revenue', function () {
    seedExperiment(treated: 1000, control: 1000, treatedRate: 0.06, controlRate: 0.06);
    DB::table('touches')->insertUsing(['tenant_id', 'subject_hash', 'reference', 'delivered_at'],
        DB::table('exposures')->where('arm', 'treatment')->selectRaw("tenant_id, subject_hash, 't-' || id, eligible_at + interval '1 hour'"));

    $report = profitReport();

    // Every treated order lands within 72h of a message, so last-touch "credits" all of it…
    expect($report['attributed_revenue_halalas'])->toBeGreaterThan(0)
        // …while the honest estimate is a range around zero.
        ->and($report['incremental_revenue_halalas']['verdict'])->toBe('inconclusive');
});

it('counts a discount once: inside the net sale, not again as a cost', function () {
    $t = $this->team->id;

    foreach (['b1', 'b2'] as $i => $buyer) {
        $hash = hash('sha256', $buyer);
        DB::table('exposures')->insert(['tenant_id' => $t, 'experiment_id' => $this->experimentId, 'subject_hash' => $hash, 'arm' => 'treatment', 'cart_id' => $i + 1, 'cart_total_halalas' => 20000, 'eligible_at' => '2026-11-01 10:00']);
        DB::table('orders')->insert(['tenant_id' => $t, 'external_id' => "o{$i}", 'phone_hash' => $hash, 'total_halalas' => 18000, 'discount_halalas' => 2000, 'placed_at' => '2026-11-02', 'currency' => 'SAR']);
        DB::table('cost_ledger_entries')->insert(['tenant_id' => $t, 'kind' => 'discount', 'amount_micros' => 20_000_000, 'reference' => "order:{$i}", 'subject_hash' => $hash, 'meta' => '{}', 'occurred_at' => '2026-11-02']);
    }

    foreach (['n1', 'n2'] as $i => $nonBuyer) {
        DB::table('exposures')->insert(['tenant_id' => $t, 'experiment_id' => $this->experimentId, 'subject_hash' => hash('sha256', $nonBuyer), 'arm' => 'control', 'cart_id' => $i + 10, 'cart_total_halalas' => 20000, 'eligible_at' => '2026-11-01 10:00']);
    }

    $report = profitReport();

    // Shown in the costs…
    expect($report['costs_halalas']['discount'])->toBe(4000)
        // …but the contribution is 180 SAR × 40% = 72 SAR per buyer, not 72 − 20.
        ->and($report['incremental_profit_halalas']['per_customer']['low'])->toEqual(7200.0);
});

it('subtracts shipping and refunds from a sale, and counts a cancelled order as nothing', function (array $order, float $profit, float $revenue) {
    $t = $this->team->id;

    foreach (['b1', 'b2'] as $i => $buyer) {
        $hash = hash('sha256', $buyer);
        DB::table('exposures')->insert(['tenant_id' => $t, 'experiment_id' => $this->experimentId, 'subject_hash' => $hash, 'arm' => 'treatment', 'cart_id' => $i + 1, 'cart_total_halalas' => 20000, 'eligible_at' => '2026-11-01 10:00']);
        DB::table('orders')->insert(['tenant_id' => $t, 'external_id' => "o{$i}", 'phone_hash' => $hash, 'total_halalas' => 20000, 'placed_at' => '2026-11-02', 'currency' => 'SAR'] + $order);
    }

    foreach (['n1', 'n2'] as $i => $nonBuyer) {
        DB::table('exposures')->insert(['tenant_id' => $t, 'experiment_id' => $this->experimentId, 'subject_hash' => hash('sha256', $nonBuyer), 'arm' => 'control', 'cart_id' => $i + 10, 'cart_total_halalas' => 20000, 'eligible_at' => '2026-11-01 10:00']);
    }

    $report = profitReport();

    expect($report['incremental_profit_halalas']['per_customer']['low'])->toEqual($profit)
        ->and($report['incremental_revenue_halalas']['per_customer']['low'])->toEqual($revenue);
})->with([
    // (200 − 20 shipping − 50 refunded) SAR = 130 SAR net, × 40% margin = 52 SAR.
    'partial refund' => [['shipping_halalas' => 2000, 'refunded_halalas' => 5000], 5200.0, 13000.0],
    'full refund' => [['refunded_halalas' => 20000], 0.0, 0.0],
    'cancelled' => [['cancelled_at' => '2026-11-03'], 0.0, 0.0],
]);

it('leaves out customers whose outcome window has not closed yet', function () {
    DB::table('exposures')->insert(['tenant_id' => $this->team->id, 'experiment_id' => $this->experimentId, 'subject_hash' => 'x', 'arm' => 'treatment', 'cart_id' => 1, 'cart_total_halalas' => 1, 'eligible_at' => '2026-11-30 10:00']);

    $this->travelTo(CarbonImmutable::parse('2026-12-02'));

    expect(profitReport()['sample']['not_yet_matured'])->toBe(1);
});

it('freezes the report when the split is broken (SRM)', function () {
    DB::table('experiments')->update(['holdout_bps' => 1000]);
    $rows = [];
    foreach (range(1, 1000) as $i) {
        $rows[] = ['tenant_id' => $this->team->id, 'experiment_id' => $this->experimentId, 'subject_hash' => hash('sha256', (string) $i), 'arm' => $i <= 400 ? 'control' : 'treatment', 'assigned_at' => now()];
    }
    DB::table('experiment_assignments')->insert($rows);

    $p = inTenant(fn () => app(SampleRatioCheck::class)->run());

    expect($p)->toBeLessThan(0.001)
        ->and(profitReport()['status'])->toBe('frozen')
        ->and(DB::table('audit_events')->where('action', 'experiment.srm_flagged')->exists())->toBeTrue();
});

it('does not flag a healthy split', function () {
    $rows = [];
    foreach (range(1, 1000) as $i) {
        $rows[] = ['tenant_id' => $this->team->id, 'experiment_id' => $this->experimentId, 'subject_hash' => hash('sha256', (string) $i), 'arm' => $i <= 103 ? 'control' : 'treatment', 'assigned_at' => now()];
    }
    DB::table('experiment_assignments')->insert($rows);

    expect(inTenant(fn () => app(SampleRatioCheck::class)->run()))->toBeGreaterThan(0.001)
        ->and(profitReport()['status'])->toBe('ok');
});

it('assigns about 10% to control, always the same arm for the same customer', function () {
    $arms = inTenant(function () {
        $assigner = app(ExperimentAssigner::class);
        $result = [];

        foreach (range(1, 10_000) as $i) {
            $result[] = $assigner->arm(hash('sha256', "c{$i}"))->value;
        }

        expect($assigner->arm(hash('sha256', 'c1'))->value)->toBe($result[0]);

        return array_count_values($result);
    });

    expect($arms['control'])->toBeGreaterThanOrEqual(900)->toBeLessThanOrEqual(1100);
});

it('lets the owner read the report and not the agent', function () {
    $owner = User::factory()->create();
    $agent = User::factory()->create();
    $this->team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($agent, ['role' => TeamRole::Agent->value]);

    $this->actingAs($owner)->getJson("/api/v1/teams/{$this->team->slug}/reports/profit?from=2026-11-01&to=2026-12-01")
        ->assertOk()->assertJsonPath('data.status', 'ok');
    $this->actingAs($agent)->getJson("/api/v1/teams/{$this->team->slug}/reports/profit")->assertForbidden();
});

it('cuts periods on Riyadh days, with the "to" day included (review H6)', function () {
    $owner = User::factory()->create();
    $this->team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $url = "/api/v1/teams/{$this->team->slug}/reports/profit";

    // 00:30 on 1 November in Riyadh is still 31 October in UTC.
    DB::table('exposures')->insert(['tenant_id' => $this->team->id, 'experiment_id' => $this->experimentId, 'subject_hash' => hash('sha256', 'late-night'), 'arm' => 'treatment', 'cart_id' => 1, 'cart_total_halalas' => 20000, 'eligible_at' => CarbonImmutable::parse('2026-11-01 00:30', 'Asia/Riyadh')->utc()]);

    $count = fn (string $from, string $to) => array_sum($this->actingAs($owner)->getJson("{$url}?from={$from}&to={$to}")->assertOk()->json('data.sample'));

    expect($count('2026-11-01', '2026-11-01'))->toBe(1)
        ->and($count('2026-10-31', '2026-10-31'))->toBe(0)
        ->and($this->actingAs($owner)->getJson("{$url}?from=2026-11-01&to=2026-11-30")->json('data.period'))->toBe(['from' => '2026-11-01', 'to' => '2026-11-30']);
});
