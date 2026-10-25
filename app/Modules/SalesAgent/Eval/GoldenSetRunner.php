<?php

namespace App\Modules\SalesAgent\Eval;

use App\Models\Team;
use App\Modules\AiGateway\AiGateway;
use App\Modules\AiGateway\ResponseValidator;
use App\Modules\AiGateway\Testing\FakeLlm;
use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\Data\ProductData;
use App\Modules\Commerce\Data\VariantData;
use App\Modules\Knowledge\KnowledgeBase;
use App\Modules\Knowledge\KnowledgeKind;
use App\Modules\SalesAgent\Models\EvalRun;
use App\Modules\SalesAgent\SalesAgent;
use App\Modules\SalesAgent\SystemPrompt;
use App\Shared\Tenancy\TenantContext;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
     * @return array{results: list<CaseResult>, run: EvalRun}
     */
    public function run(bool $live, ?Closure $progress = null, int $pauseSeconds = 0): array
    {
        $cases = $this->set->cases();
        $results = [];

        DB::beginTransaction();

        try {
            $team = Team::query()->create(['name' => (string) ($this->set->store()['name'] ?? 'Golden store'), 'slug' => 'golden-'.Str::lower(Str::random(10)), 'is_personal' => false]);

            $results = $this->tenants->run($team->id, function () use ($cases, $live, $progress, $pauseSeconds) {
                $this->seed($this->set->store());
                $results = [];

                foreach ($cases as $case) {
                    $result = $this->runCase($case, $live);

                    // Free provider tiers rate-limit: wait and try the case once more, so the run measures the model.
                    if ($live && $pauseSeconds > 0 && in_array('expected a reply, got handoff:provider_unavailable', $result->problems, true)) {
                        sleep($pauseSeconds * 3);
                        $result = $this->runCase($case, $live);
                    }

                    if ($live && $pauseSeconds > 0) {
                        sleep($pauseSeconds);
                    }

                    $results[] = $result;

                    if ($progress !== null) {
                        $progress($result);
                    }
                }

                return $results;
            });
        } finally {
            DB::rollBack();
        }

        return ['results' => $results, 'run' => $this->record($results, $live)];
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

        if ($case->outcome === 'draft' && $decision->text === null) {
            $problems[] = 'expected a reply, got handoff:'.$decision->handoff?->value;
        }

        if ($case->outcome === 'handoff' && $decision->handoff === null) {
            $problems[] = 'expected a handoff, got a reply';
        }

        if ($case->reason !== null && $decision->handoff !== null && $decision->handoff->value !== $case->reason) {
            $problems[] = "expected handoff {$case->reason}, got {$decision->handoff->value}";
        }

        foreach (array_diff($case->tools, $decision->tools) as $tool) {
            $problems[] = "did not call {$tool}";
        }

        $text = mb_strtolower(ResponseValidator::normalizeNumbers((string) $decision->text));

        foreach ($case->contains as $needle) {
            if (! str_contains($text, mb_strtolower(ResponseValidator::normalizeNumbers($needle)))) {
                $problems[] = "reply lacks \"{$needle}\"";
            }
        }

        foreach ($case->notContains as $needle) {
            if ($decision->text !== null && str_contains($text, mb_strtolower(ResponseValidator::normalizeNumbers($needle)))) {
                $problems[] = "reply contains \"{$needle}\"";
            }
        }

        DB::table('carts')->where('phone_hash', $subject)->delete();

        return new CaseResult($case, $problems === [] ? CaseResult::PASSED : CaseResult::FAILED, $problems, $decision->text, $decision->costUsdMicros);
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
     * @param  array<string, mixed>  $store
     */
    private function seed(array $store): void
    {
        $recorder = app(CommerceRecorder::class);

        foreach ((array) ($store['products'] ?? []) as $product) {
            $product = (array) $product;
            $recorder->recordProduct(new ProductData(
                externalId: (string) $product['id'],
                name: (string) $product['name'],
                sku: null,
                categoryExternalId: null,
                priceHalalas: (int) round(((float) $product['price_sar']) * 100),
                quantity: isset($product['quantity']) ? (int) $product['quantity'] : null,
                status: (string) ($product['status'] ?? 'sale'),
                description: isset($product['description']) ? (string) $product['description'] : null,
                url: 'https://golden.store.test/p/'.$product['id'],
                regularPriceHalalas: isset($product['was_sar']) ? (int) round(((float) $product['was_sar']) * 100) : null,
                unlimitedQuantity: (bool) ($product['unlimited'] ?? false),
                variants: array_values(array_map(fn ($v) => new VariantData(
                    (string) $v['id'],
                    (string) $v['name'],
                    (int) round(((float) $v['price_sar']) * 100),
                    isset($v['quantity']) ? (int) $v['quantity'] : null,
                ), (array) ($product['variants'] ?? []))),
            ));
        }

        $knowledge = app(KnowledgeBase::class);

        foreach ((array) ($store['policies'] ?? []) as $kind => $text) {
            $knowledge->save(null, KnowledgeKind::from((string) $kind), (string) $kind, (string) $text, null);
        }
    }

    private function cart(GoldenCase $case, string $subject): void
    {
        if ($case->cartSar === null) {
            return;
        }

        $cartId = DB::table('carts')->insertGetId([
            'tenant_id' => $this->tenants->require(),
            'external_id' => 'golden-'.$case->id,
            'phone_hash' => $subject,
            'total_halalas' => $case->cartSar * 100,
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
