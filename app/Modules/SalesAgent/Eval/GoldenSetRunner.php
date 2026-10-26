<?php

namespace App\Modules\SalesAgent\Eval;

use App\Models\Team;
use App\Modules\AiGateway\AiGateway;
use App\Modules\AiGateway\ResponseValidator;
use App\Modules\AiGateway\Testing\FakeLlm;
use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\Data\ProductData;
use App\Modules\Commerce\Data\VariantData;
use App\Modules\Knowledge\CatalogIndexer;
use App\Modules\Knowledge\KnowledgeBase;
use App\Modules\Knowledge\KnowledgeKind;
use App\Modules\SalesAgent\Models\EvalRun;
use App\Modules\SalesAgent\SalesAgent;
use App\Modules\SalesAgent\SystemPrompt;
use App\Shared\Money\Currencies;
use App\Shared\Money\Currency;
use App\Shared\Money\JsonAmount;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Tenancy\WorkspaceLocale;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Runs the golden set against the real agent decision (US-E10-05) in a
 * throw-away tenant, rolled back at the end.
 *
 * Offline (CI, no key): scripted model steps per case, plus every
 * deterministic handoff with no model at all. It proves the guards: rules,
 * validator, retry, tools. Live (a paid key, founder approval): the real
 * model, which is what the phase exit criteria measure.
 *
 * Any failed price case is blocking: the merge must not happen.
 */
final class GoldenSetRunner
{
    public function __construct(
        private readonly GoldenSet $set,
        private readonly TenantContext $tenants,
        private readonly AiGateway $gateway,
    ) {}

    /**
     * @param  Closure(CaseResult): void|null  $progress
     * @param  list<string>  $only  case ids; empty runs them all
     * @return array{results: list<CaseResult>, run: EvalRun}
     */
    public function run(bool $live, ?Closure $progress = null, int $pauseSeconds = 0, array $only = []): array
    {
        $cases = array_values(array_filter($this->set->cases(), fn (GoldenCase $case) => $only === [] || in_array($case->id, $only, true)));
        $results = [];

        DB::beginTransaction();

        try {
            // One throw-away workspace per fixture store, in that store's currency and timezone.
            foreach ($this->set->stores() as $key => $store) {
                $storeCases = array_filter($cases, fn (GoldenCase $case) => $case->store === $key);

                if ($storeCases !== []) {
                    $results += $this->runStore($store, $storeCases, $live, $progress, $pauseSeconds);
                }
            }
        } finally {
            DB::rollBack();
        }

        // Back in case order, whatever the store.
        ksort($results);
        $results = array_values($results);

        return ['results' => $results, 'run' => $this->record($results, $live)];
    }

    /**
     * @param  array<string, mixed>  $store
     * @param  array<int, GoldenCase>  $cases  keyed by their place in the set
     * @param  Closure(CaseResult): void|null  $progress
     * @return array<int, CaseResult>
     */
    private function runStore(array $store, array $cases, bool $live, ?Closure $progress, int $pauseSeconds): array
    {
        $team = Team::query()->create([
            'name' => (string) ($store['name'] ?? 'Golden store'),
            'slug' => 'golden-'.Str::lower(Str::random(10)),
            'is_personal' => false,
            'currency' => (string) $store['currency'],
            'timezone' => (string) $store['timezone'],
        ]);

        return $this->tenants->run($team->id, function () use ($store, $cases, $live, $progress, $pauseSeconds) {
            $this->seed($store);
            $results = [];

            foreach ($cases as $index => $case) {
                $result = $this->runCase($case, $live);

                // Free provider tiers rate-limit: wait and try the case once more, so the run measures the model.
                if ($live && $pauseSeconds > 0 && in_array('expected a reply, got handoff:provider_unavailable', $result->problems, true)) {
                    sleep($pauseSeconds * 3);
                    $result = $this->runCase($case, $live);
                }

                if ($live && $pauseSeconds > 0) {
                    sleep($pauseSeconds);
                }

                $results[$index] = $result;

                if ($progress !== null) {
                    $progress($result);
                }
            }

            return $results;
        });
    }

    private function runCase(GoldenCase $case, bool $live): CaseResult
    {
        if (! $live) {
            if ($case->offline !== null) {
                FakeLlm::script(array_map(fn (array $step) => isset($step['tool'])
                    ? FakeLlm::tool((string) $step['tool'], (array) ($step['args'] ?? []))
                    : FakeLlm::reply((string) ($step['reply'] ?? '')), $case->offline));
            } elseif ($case->deterministic()) {
                FakeLlm::script([]); // Any model call is a failure here.
            } else {
                return new CaseResult($case, CaseResult::LIVE_ONLY);
            }
        }

        $subject = hash('sha256', 'golden:'.$case->id);
        $this->cart($case, $subject);

        $decision = app(SalesAgent::class)->evaluate($case->turns, $subject);
        $problems = [];
        $expect = $live && $case->live !== null ? $case->live : $case;

        if ($expect->outcome === 'draft' && $decision->text === null) {
            $problems[] = 'expected a reply, got handoff:'.$decision->handoff?->value;
        }

        if ($expect->outcome === 'handoff' && $decision->handoff === null) {
            $problems[] = 'expected a handoff, got a reply';
        }

        if ($expect->reason !== null && $decision->handoff !== null && $decision->handoff->value !== $expect->reason) {
            $problems[] = "expected handoff {$expect->reason}, got {$decision->handoff->value}";
        }

        foreach (array_diff($expect->tools, $decision->tools) as $tool) {
            $problems[] = "did not call {$tool}";
        }

        $text = mb_strtolower(ResponseValidator::normalizeNumbers((string) $decision->text));

        foreach ($decision->text === null && $expect->outcome === 'any' ? [] : $expect->contains as $needle) {
            if (! str_contains($text, mb_strtolower(ResponseValidator::normalizeNumbers($needle)))) {
                $problems[] = "reply lacks \"{$needle}\"";
            }
        }

        foreach ($expect->notContains as $needle) {
            if ($decision->text !== null && str_contains($text, mb_strtolower(ResponseValidator::normalizeNumbers($needle)))) {
                $problems[] = "reply contains \"{$needle}\"";
            }
        }

        DB::table('carts')->where('phone_hash', $subject)->delete();

        return new CaseResult($case, $problems === [] ? CaseResult::PASSED : CaseResult::FAILED, $problems, $decision->text, $decision->costUsdMicros, $decision->tools);
    }

    /**
     * @param  list<CaseResult>  $results
     */
    private function record(array $results, bool $live): EvalRun
    {
        $judged = array_filter($results, fn (CaseResult $r) => $r->status !== CaseResult::LIVE_ONLY);
        $price = array_filter($judged, fn (CaseResult $r) => $r->case->price);
        $failed = array_filter($judged, fn (CaseResult $r) => $r->status === CaseResult::FAILED);

        return EvalRun::create([
            'suite' => $live ? 'golden_live' : 'golden_offline',
            'provider' => $live ? (string) config('codex.ai.provider') : 'fake',
            'model' => $this->gateway->model(),
            'prompt_version' => SystemPrompt::VERSION,
            'cases' => count($judged),
            'passed' => count($judged) - count($failed),
            'price_cases' => count($price),
            'price_passed' => count(array_filter($price, fn (CaseResult $r) => $r->status === CaseResult::PASSED)),
            'blocking_failure' => array_filter($price, fn (CaseResult $r) => $r->status === CaseResult::FAILED) !== [],
            'cost_usd_micros' => array_sum(array_map(fn (CaseResult $r) => $r->costUsdMicros, $results)),
            'failures' => array_values(array_map(fn (CaseResult $r) => ['id' => $r->case->id, 'problems' => $r->problems], $failed)),
        ]);
    }

    /**
     * Amounts are decimals in the store's currency: `price`, `was` and the
     * variants' `price` (`price_sar`/`was_sar` in a SAR store).
     *
     * @param  array<string, mixed>  $store
     */
    private function seed(array $store): void
    {
        $recorder = app(CommerceRecorder::class);
        $currency = Currencies::get((string) $store['currency']);

        foreach ((array) ($store['products'] ?? []) as $product) {
            $product = (array) $product;
            $was = self::amount($currency, $product, 'was');
            $recorder->recordProduct(new ProductData(
                externalId: (string) $product['id'],
                name: (string) $product['name'],
                sku: null,
                categoryExternalId: null,
                priceHalalas: self::amount($currency, $product, 'price') ?? throw new InvalidArgumentException("Golden product {$product['id']} has no price."),
                quantity: isset($product['quantity']) ? (int) $product['quantity'] : null,
                status: (string) ($product['status'] ?? 'sale'),
                description: isset($product['description']) ? (string) $product['description'] : null,
                url: 'https://golden.store.test/p/'.$product['id'],
                regularPriceHalalas: $was,
                unlimitedQuantity: (bool) ($product['unlimited'] ?? false),
                variants: array_values(array_map(fn ($v) => new VariantData(
                    (string) $v['id'],
                    (string) $v['name'],
                    self::amount($currency, (array) $v, 'price'),
                    isset($v['quantity']) ? (int) $v['quantity'] : null,
                ), (array) ($product['variants'] ?? []))),
            ));
        }

        // Index jobs wait for a commit that never comes (the run is rolled back): index in place.
        app(CatalogIndexer::class)->reindexAll();

        $knowledge = app(KnowledgeBase::class);

        foreach ((array) ($store['policies'] ?? []) as $kind => $text) {
            $knowledge->save(null, KnowledgeKind::from((string) $kind), (string) $kind, (string) $text, null);
        }
    }

    /**
     * `$key` (or `{$key}_sar` in a SAR store) → minor units of the store currency; null when absent.
     *
     * @param  array<string, mixed>  $row
     */
    private static function amount(Currency $currency, array $row, string $key): ?int
    {
        $decimal = JsonAmount::decimal($row[$key] ?? ($currency->code === 'SAR' ? ($row["{$key}_sar"] ?? null) : null));

        if ($decimal === null && isset($row["{$key}_sar"])) {
            throw new InvalidArgumentException("Golden store in {$currency->code}: write {$key}, not {$key}_sar.");
        }

        return $decimal === null ? null : $currency->toMinor($decimal);
    }

    private function cart(GoldenCase $case, string $subject): void
    {
        if ($case->cart === null) {
            return;
        }

        $cartId = DB::table('carts')->insertGetId([
            'tenant_id' => $this->tenants->require(),
            'external_id' => 'golden-'.$case->id,
            'phone_hash' => $subject,
            'total_halalas' => app(WorkspaceLocale::class)->currency()->toMinor($case->cart),
            'currency' => app(WorkspaceLocale::class)->currency()->code,
            'checkout_url' => 'https://golden.store.test/checkout/'.$case->id,
            'status' => 'abandoned',
            'abandoned_at' => now(),
            'discovered_by' => 'webhook',
        ]);

        foreach ($case->cartItems as $productId) {
            DB::table('cart_items')->insert(['tenant_id' => $this->tenants->require(), 'cart_id' => $cartId, 'product_external_id' => $productId, 'quantity' => 1]);
        }
    }
}
