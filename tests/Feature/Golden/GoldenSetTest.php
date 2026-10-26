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

/** A golden directory with the default store and the given extra files. */
function goldenDir(array $files): string
{
    $dir = sys_get_temp_dir().'/golden-'.uniqid();
    mkdir($dir.'/cases', 0777, true);
    copy(base_path('evals/golden/store.yaml'), $dir.'/store.yaml');

    foreach ($files as $name => $yaml) {
        file_put_contents($dir.'/'.$name, $yaml);
    }

    return $dir;
}

it('runs cases against more than one fixture store, each in its own currency', function () {
    $dir = goldenDir([
        'store-kw.yaml' => <<<'YAML'
        name: متجر الكويت
        currency: KWD
        timezone: Asia/Kuwait
        products:
          - {id: oud, name: دهن عود, price: "12.345", quantity: 3}
        YAML,
        'cases/kw.yaml' => <<<'YAML'
        - id: kw-price
          store: kw
          category: price
          say: بكم دهن العود؟
          expect: {outcome: draft, contains: ["12.345"], price: true}
          offline:
            - {tool: get_product, args: {product_id: oud}}
            - {reply: "دهن العود بـ 12.345 دينار."}
        - {id: kw-human, store: kw, category: human, say: ابي اكلم موظف, cart: "10.5", cart_items: [oud], expect: {outcome: handoff, reason: asked_human}}
        - {id: sa-human, category: human, say: ابي اكلم موظف, cart_sar: 100, expect: {outcome: handoff, reason: asked_human}}
        YAML,
    ]);
    app()->bind(GoldenSet::class, fn () => new GoldenSet($dir));

    $set = app(GoldenSet::class);
    expect($set->stores()['kw']['currency'])->toBe('KWD')
        ->and($set->store()['currency'])->toBe('SAR')
        ->and(array_map(fn ($case) => [$case->id, $case->store, $case->cart], $set->cases()))->toBe([
            ['kw-price', 'kw', null], ['kw-human', 'kw', '10.5'], ['sa-human', 'default', '100'],
        ]);

    ['results' => $results] = app(GoldenSetRunner::class)->run(live: false);

    expect(array_map(fn (CaseResult $r) => [$r->case->id, $r->status, $r->problems], $results))->toBe([
        ['kw-price', CaseResult::PASSED, []],
        ['kw-human', CaseResult::PASSED, []],
        ['sa-human', CaseResult::PASSED, []],
    ]);
});

it('refuses riyal fields in a store or case in another currency, and unknown stores', function (array $files, string $error) {
    app()->bind(GoldenSet::class, fn () => new GoldenSet(goldenDir($files)));

    expect(fn () => app(GoldenSet::class)->cases())->toThrow(InvalidArgumentException::class, $error);
})->with([
    'cart_sar in EGP' => [[
        'store-eg.yaml' => "currency: EGP\nproducts: []\n",
        'cases/x.yaml' => "- {id: x, store: eg, category: human, say: hi, cart_sar: 5, expect: {outcome: handoff}}\n",
    ], 'use cart, not cart_sar'],
    'unknown store' => [[
        'cases/x.yaml' => "- {id: x, store: nope, category: human, say: hi, expect: {outcome: handoff}}\n",
    ], "no golden store 'nope'"],
    'unsupported currency' => [[
        'store-eu.yaml' => "currency: EUR\n",
        'cases/x.yaml' => "- {id: x, category: human, say: hi, expect: {outcome: handoff}}\n",
    ], "unsupported currency 'EUR'"],
]);
