<?php

use App\Models\Team;
use App\Modules\AiGateway\AgentTool;
use App\Modules\AiGateway\AiGateway;
use App\Modules\AiGateway\AiStatus;
use App\Modules\AiGateway\Data\AiRequest;
use App\Modules\AiGateway\Data\ChatTurn;
use App\Modules\AiGateway\Sdk\GatewayAgent;
use App\Modules\AiGateway\Testing\FakeLlm;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    app(TenantContext::class)->set($this->team->id);
});

function priceTool(): AgentTool
{
    return new class implements AgentTool
    {
        public function name(): string
        {
            return 'get_product';
        }

        public function description(): string
        {
            return 'Price and stock of a product.';
        }

        public function schema(JsonSchema $schema): array
        {
            return ['product_id' => $schema->string()->required()];
        }

        public function handle(array $arguments): array
        {
            return ['product_id' => $arguments['product_id'], 'name' => 'Oud 100ml', 'price_halalas' => 19900, 'quantity' => 4];
        }
    };
}

function ask(string $text, int $capUsdMicros = 5_000_000, array $names = []): AiRequest
{
    return new AiRequest(
        purpose: 'sales_agent',
        instructions: 'You are a store assistant.',
        history: [ChatTurn::customer($text)],
        tools: [priceTool()],
        dailyCapUsdMicros: $capUsdMicros,
        conversationRef: 'conv:1',
        subjectHash: str_repeat('a', 64),
        knownNames: $names,
    );
}

it('runs the tools through the SDK loop and accepts a reply built from them', function () {
    FakeLlm::script([
        FakeLlm::tool('get_product', ['product_id' => 'p1']),
        FakeLlm::reply('عطر Oud 100ml سعره ١٩٩ ريال، وباقي منه 4.', inputTokens: 3000, outputTokens: 200),
    ]);

    $reply = app(AiGateway::class)->converse(ask('بكم العود؟'));

    expect($reply->ok())->toBeTrue()
        ->and($reply->calledTool('get_product'))->toBeTrue()
        ->and($reply->tools[0]->result['price_halalas'])->toBe(19900);
});

it('meters every call: tokens and cost per tenant, the cost ledger and usage', function () {
    FakeLlm::script([FakeLlm::reply('أهلًا، كيف أقدر أساعدك؟', inputTokens: 3000, outputTokens: 200)]);

    $reply = app(AiGateway::class)->converse(ask('السلام عليكم'));

    // Haiku 5.5: 3000 × $0.10 + 200 × $0.50 per 1M tokens = $0.0004.
    expect($reply->costUsdMicros)->toBe(400);

    $call = DB::table('ai_calls')->first();
    expect($call->tenant_id)->toBe($this->team->id)
        ->and($call->model)->toBe('claude-haiku-5-5')
        ->and($call->input_tokens)->toBe(3000)
        ->and($call->cost_usd_micros)->toBe(400)
        ->and($call->conversation_ref)->toBe('conv:1');

    $ledger = DB::table('cost_ledger_entries')->where('kind', 'llm')->first();
    expect($ledger->amount_micros)->toBe(1500) // SAR micros at 3.75
        ->and($ledger->subject_hash)->toBe(str_repeat('a', 64));

    expect(DB::table('usage_daily')->where('metric', 'ai_cost_usd_micros')->value('count'))->toBe(400);
});

it('blocks a reply whose price no tool returned', function () {
    FakeLlm::script([
        FakeLlm::tool('get_product', ['product_id' => 'p1']),
        FakeLlm::reply('سعره 179 ريال بس اليوم'),
    ]);

    $reply = app(AiGateway::class)->converse(ask('بكم العود؟'));

    expect($reply->ok())->toBeFalse()
        ->and($reply->validation->unmatched)->toBe(['179']);
});

it('stops at the daily cap without calling the model', function () {
    DB::table('ai_calls')->insert([
        'tenant_id' => $this->team->id, 'purpose' => 'sales_agent', 'provider' => 'anthropic', 'model' => 'claude-haiku-5-5',
        'status' => 'ok', 'cost_usd_micros' => 5_000_000, 'created_at' => now(),
    ]);
    FakeLlm::script([]);

    $reply = app(AiGateway::class)->converse(ask('بكم العود؟'));

    expect($reply->status)->toBe(AiStatus::CapReached)->and($reply->ok())->toBeFalse();
    GatewayAgent::assertNeverPrompted();
});

it('counts only today in Riyadh against the cap', function () {
    DB::table('ai_calls')->insert([
        'tenant_id' => $this->team->id, 'purpose' => 'sales_agent', 'provider' => 'anthropic', 'model' => 'claude-haiku-5-5',
        'status' => 'ok', 'cost_usd_micros' => 5_000_000, 'created_at' => now('Asia/Riyadh')->startOfDay()->subMinute()->utc(),
    ]);
    FakeLlm::script([FakeLlm::reply('أهلًا')]);

    expect(app(AiGateway::class)->converse(ask('هلا'))->ok())->toBeTrue();
});

it('turns a provider outage into a status, never an exception', function () {
    FakeLlm::script([FakeLlm::down()]);

    $reply = app(AiGateway::class)->converse(ask('بكم العود؟'));

    expect($reply->status)->toBe(AiStatus::ProviderUnavailable)
        ->and(DB::table('ai_calls')->value('status'))->toBe('provider_unavailable');
});

it('sends zero personal data to the model across 1,000 calls (US-E10-04)', function () {
    $faker = fake('ar_SA');
    $secrets = [];
    $steps = [];

    for ($i = 0; $i < 1000; $i++) {
        $steps[] = FakeLlm::reply('تمام يا [NAME_A]، بنتواصل معك.');
    }
    FakeLlm::script($steps);

    for ($i = 0; $i < 1000; $i++) {
        $name = trim($faker->firstName());
        $local = '05'.$faker->numerify('########');
        $phone = match ($i % 4) {
            0 => $local,
            1 => '+966 '.substr($local, 1, 2).' '.substr($local, 3, 3).' '.substr($local, 6),
            2 => strtr($local, ['0' => '٠', '1' => '١', '2' => '٢', '3' => '٣', '4' => '٤', '5' => '٥', '6' => '٦', '7' => '٧', '8' => '٨', '9' => '٩']),
            default => '966-'.substr($local, 1),
        };
        $email = 'c'.$i.'@example.com';
        $secrets[] = [$name, preg_replace('/\D/', '', strtr($phone, ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9'])), $email];

        $reply = app(AiGateway::class)->converse(new AiRequest(
            purpose: 'sales_agent',
            instructions: 'You are a store assistant.',
            history: [
                ChatTurn::customer("انا {$name} ورقمي {$phone}"),
                ChatTurn::assistant('أهلًا بك'),
                ChatTurn::customer("ايميلي {$email} كلموني يا جماعة"),
            ],
            tools: [],
            dailyCapUsdMicros: PHP_INT_MAX,
            knownNames: [$name],
        ));

        expect($reply->text)->toContain($name);
    }

    $leaks = 0;
    GatewayAgent::assertNotPrompted(function (AgentPrompt $prompt) use (&$secrets, &$leaks) {
        $payload = $prompt->prompt.' '.$prompt->agent->instructions();

        foreach ($prompt->agent->messages() as $message) {
            $payload .= ' '.$message->content;
        }
        $digits = preg_replace('/\D/', '', $payload);

        foreach ($secrets as [$name, $phone, $email]) {
            if (str_contains($payload, $email) || str_contains($digits, substr($phone, -9)) || preg_match('/(^|\s)'.preg_quote($name, '/').'(\s|،|$)/u', $payload) === 1) {
                $leaks++;

                return true;
            }
        }

        return false;
    });

    expect($leaks)->toBe(0)
        ->and(DB::table('ai_calls')->where('pseudonymized', '>=', 3)->count())->toBe(1000);
});
