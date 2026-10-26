<?php

namespace App\Modules\AiGateway;

use App\Modules\AiGateway\Data\AiReply;
use App\Modules\AiGateway\Data\AiRequest;
use App\Modules\AiGateway\Data\ChatTurn;
use App\Modules\AiGateway\Demo\DemoModel;
use App\Modules\AiGateway\Models\AiCall;
use App\Modules\AiGateway\Sdk\GatewayAgent;
use App\Modules\AiGateway\Sdk\ToolAdapter;
use App\Modules\AiGateway\Sdk\ToolRecorder;
use App\Modules\Billing\UsageMeter;
use App\Modules\Measurement\CostLedger;
use App\Shared\Tenancy\WorkspaceLocale;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Messages\AssistantMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Responses\Data\TextUsage;
use Throwable;

/**
 * The only door to an LLM (M07, US-E10-01; an arch test forbids any other).
 * Every call is capped per tenant, pseudonymized, metered into `ai_calls`,
 * the cost ledger and usage, and its reply validated. A provider failure is
 * a status, never an exception, so the caller always hands off safely (S8).
 */
class AiGateway
{
    /**
     * @param  array<string, array{input: float, output: float, cache_read: float, cache_write: float}>  $pricing
     * @param  array{enabled: bool, provider: string, model: string, dimensions: int, usd_per_mtok: float}  $embeddings
     */
    public function __construct(
        private readonly CostLedger $ledger,
        private readonly UsageMeter $usage,
        private readonly ResponseValidator $validator,
        private readonly string $provider,
        private readonly string $model,
        private readonly array $pricing,
        private readonly int $timeoutSeconds,
        private readonly int $maxSteps,
        private readonly int $maxOutputTokens,
        private readonly array $embeddings,
    ) {}

    public function converse(AiRequest $request): AiReply
    {
        // An unpriced model would bill without being counted against the cap (review: correctness #5).
        if (! isset($this->pricing[$this->model])) {
            Log::error('AI model has no price configured; refusing the call.', ['model' => $this->model]);
            $call = $this->record($request, AiStatus::Failed, new TextUsage, 0, 0, 0, 'unpriced_model');

            return AiReply::unavailable(AiStatus::Failed, $call->id);
        }

        if ($this->spentTodayUsdMicros() >= $request->dailyCapUsdMicros) {
            $call = $this->record($request, AiStatus::CapReached, new TextUsage, 0, 0, 0);

            return AiReply::unavailable(AiStatus::CapReached, $call->id);
        }

        $pseudonyms = new Pseudonymizer($request->knownNames);
        $turns = array_map(
            fn (ChatTurn $turn) => new ChatTurn($turn->role, $pseudonyms->pseudonymize($turn->text)),
            $request->history,
        );
        $last = array_pop($turns);

        $recorder = new ToolRecorder;
        $tools = array_map(fn (AgentTool $tool) => new ToolAdapter($tool, $recorder), $request->tools);

        $agent = (new GatewayAgent(
            $request->instructions,
            array_map(fn (ChatTurn $turn) => $turn->role === ChatTurn::ASSISTANT
                ? new AssistantMessage($turn->text)
                : new UserMessage($turn->text), $turns),
            $tools,
        ))->limitedTo($this->maxSteps, $this->maxOutputTokens, $this->timeoutSeconds);

        $provider = $this->provider;

        if ($provider === DemoModel::PROVIDER && ! GatewayAgent::isFaked()) {
            if (! DemoModel::allowed()) {
                return AiReply::unavailable(AiStatus::ProviderUnavailable);
            }

            DemoModel::script($last->text ?? '');
            $provider = 'anthropic'; // The SDK needs a configured provider to fake.
        }

        $started = hrtime(true);

        try {
            $response = $agent->prompt($last->text ?? '', provider: $provider, model: $this->model);
        } catch (Throwable $exception) {
            Log::warning('AI provider unavailable; handing off.', ['error' => $exception::class]);
            $call = $this->record($request, AiStatus::ProviderUnavailable, new TextUsage, self::elapsedMs($started), count($recorder->outcomes), $pseudonyms->count(), $exception::class);

            return AiReply::unavailable(AiStatus::ProviderUnavailable, $call->id);
        }

        $cost = $this->costUsdMicros($response->usage);
        $call = $this->record($request, AiStatus::Ok, $response->usage, self::elapsedMs($started), count($recorder->outcomes), $pseudonyms->count(), cost: $cost);

        // The validator reads the model's own words, before names come back.
        $validation = $this->validator->validate($response->text, $recorder->outcomes, $request->trustedTexts);

        return new AiReply(AiStatus::Ok, $pseudonyms->restore($response->text), $recorder->outcomes, $validation, $cost, $call->id);
    }

    /**
     * Embeddings for search (E09), metered like any call. Null when the
     * provider is unavailable: search then runs on text alone.
     *
     * @param  list<string>  $texts
     * @return list<list<float>>|null
     */
    public function embed(array $texts, string $purpose): ?array
    {
        if ($texts === []) {
            return [];
        }

        if (! $this->embeddings['enabled']) {
            return null;
        }

        $request = new AiRequest($purpose, '', [], [], 0);
        $started = hrtime(true);

        try {
            $response = Embeddings::for($texts)
                ->dimensions($this->embeddings['dimensions'])
                ->timeout(min(5, $this->timeoutSeconds)) // search must not hold the agent's turn
                ->generate($this->embeddings['provider'], $this->embeddings['model']);
        } catch (Throwable $exception) {
            Log::warning('Embeddings provider unavailable.', ['error' => $exception::class]);
            $this->record($request, AiStatus::ProviderUnavailable, new TextUsage, self::elapsedMs($started), 0, 0, $exception::class, model: $this->embeddings['model'], provider: $this->embeddings['provider']);

            return null;
        }

        $tokens = $response->usage->inputTokens;
        $cost = (int) round($tokens * $this->embeddings['usd_per_mtok']);
        $this->record($request, AiStatus::Ok, new TextUsage($tokens), self::elapsedMs($started), 0, 0, cost: $cost, model: $this->embeddings['model'], provider: $this->embeddings['provider']);

        /** @var list<list<float>> */
        return array_values($response->embeddings);
    }

    /** Spend since local midnight in the workspace timezone, the day the merchant sees (US-E10-06, ADR-012). */
    public function spentTodayUsdMicros(): int
    {
        $since = CarbonImmutable::now(app(WorkspaceLocale::class)->timezone())->startOfDay()->utc();

        return (int) AiCall::query()->where('created_at', '>=', $since)->sum('cost_usd_micros');
    }

    public function model(): string
    {
        return $this->model;
    }

    /** USD micros: tokens × $/1M tokens. */
    public function costUsdMicros(TextUsage $usage): int
    {
        $price = $this->pricing[$this->model] ?? null;

        if ($price === null) {
            // An unpriced model must never look free.
            throw new \RuntimeException("No price configured for {$this->model}.");
        }

        $cacheRead = $usage->cacheReadInputTokens ?? 0;
        $cacheWrite = $usage->cacheWriteInputTokens ?? 0;

        return (int) round(
            max(0, $usage->inputTokens - $cacheRead - $cacheWrite) * $price['input']
            + $cacheRead * $price['cache_read']
            + $cacheWrite * $price['cache_write']
            + $usage->outputTokens * $price['output']
        );
    }

    private function record(
        AiRequest $request,
        AiStatus $status,
        TextUsage $usage,
        int $latencyMs,
        int $toolCalls,
        int $pseudonymized,
        ?string $error = null,
        int $cost = 0,
        ?string $model = null,
        ?string $provider = null,
    ): AiCall {
        $call = AiCall::create([
            'purpose' => $request->purpose,
            'conversation_ref' => $request->conversationRef,
            'provider' => $provider ?? $this->provider,
            'model' => $model ?? $this->model,
            'status' => $status->value,
            'input_tokens' => $usage->inputTokens,
            'output_tokens' => $usage->outputTokens,
            'cache_read_tokens' => $usage->cacheReadInputTokens ?? 0,
            'cache_write_tokens' => $usage->cacheWriteInputTokens ?? 0,
            'cost_usd_micros' => $cost,
            'latency_ms' => $latencyMs,
            'tool_calls' => $toolCalls,
            'pseudonymized' => $pseudonymized,
            'error' => $error === null ? null : substr($error, 0, 120),
        ]);

        $now = CarbonImmutable::now();
        $this->usage->increment(UsageMeter::AI_CALLS, 1, $now);

        if ($cost > 0) {
            $this->usage->increment(UsageMeter::AI_COST_USD_MICROS, $cost, $now);
            $this->ledger->recordLlm('ai_call:'.$call->id, $request->subjectHash, $cost, $now, $call->model);
        }

        return $call;
    }

    private static function elapsedMs(int|float $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }
}
