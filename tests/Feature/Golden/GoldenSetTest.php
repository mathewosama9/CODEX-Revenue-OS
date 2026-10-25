<?php

use App\Modules\SalesAgent\Eval\CaseResult;
use App\Modules\SalesAgent\Eval\GoldenSet;
use App\Modules\SalesAgent\Eval\GoldenSetRunner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/*
| The golden set in CI (US-E10-05), offline: scripted model steps and the
| deterministic handoffs. Any failure blocks the merge; a price or stock
| failure is called out on its own. The live run is `php artisan ai:eval --live`.
*/

it('passes every offline golden case, price and stock included', function () {
    ['results' => $results, 'run' => $run] = app(GoldenSetRunner::class)->run(live: false);

    $failures = array_values(array_map(
        fn (CaseResult $r) => $r->case->id.': '.implode('; ', $r->problems),
        array_filter($results, fn (CaseResult $r) => $r->status === CaseResult::FAILED),
    ));

    expect($failures)->toBe([])
        ->and($run->blocking_failure)->toBeFalse()
        ->and($run->price_cases)->toBeGreaterThanOrEqual(20)
        ->and(DB::table('eval_runs')->count())->toBe(1);
});

it('keeps the starter set balanced across the categories of 06 B.5.2', function () {
    $cases = app(GoldenSet::class)->cases();
    $categories = array_count_values(array_map(fn ($case) => $case->category, $cases));

    expect(count($cases))->toBeGreaterThanOrEqual(60)
        ->and(count(array_filter($cases, fn ($case) => $case->dialect === 'egyptian')))->toBeGreaterThanOrEqual(10)
        ->and(array_keys($categories))->toContain('price', 'stock', 'discount', 'out_of_scope', 'complaint', 'injection', 'policy');
});

it('blocks the merge when a price case fails', function () {
    $dir = sys_get_temp_dir().'/golden-'.uniqid();
    mkdir($dir.'/cases', 0777, true);
    copy(base_path('evals/golden/store.yaml'), $dir.'/store.yaml');
    file_put_contents($dir.'/cases/bad.yaml', <<<'YAML'
    - id: bad-price
      category: price
      say: بكم العود؟
      expect: {outcome: draft, contains: ["199"], price: true}
      offline:
        - {tool: get_product, args: {product_id: musk}}
        - {reply: "سعره 89 ريال"}
    YAML);
    app()->bind(GoldenSet::class, fn () => new GoldenSet($dir));

    $this->artisan('ai:eval')->assertFailed();

    expect(DB::table('eval_runs')->value('blocking_failure'))->toBeTrue();
});
