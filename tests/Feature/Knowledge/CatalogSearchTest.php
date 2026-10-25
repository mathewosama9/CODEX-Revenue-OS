<?php

use App\Models\Team;
use App\Models\User;
use App\Modules\Commerce\CommerceRecorder;
use App\Modules\Commerce\Data\ProductData;
use App\Modules\Knowledge\CatalogSearch;
use App\Modules\Knowledge\KnowledgeBase;
use App\Modules\Knowledge\KnowledgeKind;
use App\Modules\Knowledge\KnowledgeRetriever;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Embeddings;
use Laravel\Ai\Prompts\EmbeddingsPrompt;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->other = Team::factory()->create();
    app(TenantContext::class)->set($this->team->id);
});

function stock(string $id, string $name, ?string $description = null, string $status = 'sale', ?int $tenant = null): void
{
    $record = fn () => app(CommerceRecorder::class)->recordProduct(new ProductData($id, $name, null, null, 19900, 5, $status, $description));

    $tenant === null ? $record() : app(TenantContext::class)->run($tenant, $record);
}

function found(string $query, int $limit = 3): array
{
    return array_map(fn ($hit) => $hit->productExternalId, app(CatalogSearch::class)->search($query, $limit));
}

it('finds a product however the customer spells it in Arabic (US-E09-03)', function (string $query) {
    stock('oud', 'عُود كَمبودي مَلَكي');
    stock('rose', 'ماء ورد طائفي');
    stock('musk', 'مسك أبيض');

    expect(found($query)[0] ?? null)->toBe('oud');
})->with([
    'tashkeel removed' => 'عود كمبودي',
    'hamza and taa marbuta' => 'عود ملكى',
    'Saudi filler words' => 'ابغى عود كمبودي لو سمحت',
    'Arabic-Indic and typing' => 'عندكم العود الكمبودي؟',
    'typo' => 'كمبدي',
    'Arabizi' => '3ood kambodi',
]);

it('matches alef and taa marbuta variants on both sides', function () {
    stock('rose', 'ماء ورد طائفي');

    expect(found('ورده الطائف'))->toContain('rose')
        ->and(found('أطياب'))->toBe([]);
});

it('finds a brand with a missing letter (شانل for شانيل)', function () {
    stock('chanel', 'عطر شانيل كوكو');
    stock('dior', 'عطر ديور سوفاج');

    expect(found('شانل')[0] ?? null)->toBe('chanel');
});

it('never offers hidden or deleted products (US-E09-05)', function () {
    stock('hidden', 'عود هندي', status: 'hidden');
    stock('deleted', 'عود هندي قديم', status: 'deleted');
    stock('ok', 'عود كمبودي');

    expect(found('عود'))->toBe(['ok']);
});

it('never returns another store products', function () {
    stock('mine', 'مسك أبيض');
    stock('theirs', 'مسك أبيض فاخر', tenant: $this->other->id);

    expect(found('مسك'))->toBe(['mine']);
});

it('adds meaning through embeddings when they are on', function () {
    config(['codex.ai.embeddings.enabled' => true]);
    // A tiny fake meaning space: "occasions" points one way, everything else another.
    Embeddings::fake(fn (EmbeddingsPrompt $prompt) => array_map(
        fn (string $text) => str_contains($text, 'أعراس') || str_contains($text, 'مناسبات')
            ? [1.0, ...array_fill(0, 1023, 0.0)]
            : [0.0, 1.0, ...array_fill(0, 1022, 0.0)],
        $prompt->inputs,
    ));

    stock('wedding', 'عطر الليالي', 'فخم ويناسب الأعراس');
    stock('daily', 'عطر يومي خفيف', 'منعش للدوام');

    expect(found('شي للمناسبات')[0] ?? null)->toBe('wedding')
        ->and(DB::table('ai_calls')->where('purpose', 'catalog_index')->count())->toBeGreaterThan(0);
});

it('does not embed again when only the price changed', function () {
    config(['codex.ai.embeddings.enabled' => true]);
    Embeddings::fake();

    stock('oud', 'عود كمبودي');
    app(CommerceRecorder::class)->recordProduct(new ProductData('oud', 'عود كمبودي', null, null, 17900, 5, 'sale'));

    expect(DB::table('ai_calls')->where('purpose', 'catalog_index')->count())->toBe(1);
});

it('returns the merchant policy text, and nothing when none was written (US-E09-02)', function () {
    $user = User::factory()->create();
    $kb = app(KnowledgeBase::class);

    expect(app(KnowledgeRetriever::class)->retrieve(KnowledgeKind::Shipping))->toBe([]);

    $kb->save(null, KnowledgeKind::Shipping, 'الشحن', "التوصيل داخل الرياض خلال يومين.\n\nباقي المدن خلال ٣ إلى ٥ أيام عمل.", $user->id);
    $kb->save(null, KnowledgeKind::Faq, 'الأسئلة', "هل العطور أصلية؟ نعم، كل عطورنا أصلية ومن الوكيل.\n\nهل فيه تغليف هدايا؟ نعم، بدون رسوم.", $user->id);

    $shipping = app(KnowledgeRetriever::class)->retrieve(KnowledgeKind::Shipping);
    $faq = app(KnowledgeRetriever::class)->retrieve(KnowledgeKind::Faq, 'فيه تغليف هديه؟', limit: 1);

    expect($shipping[0]->text)->toContain('خلال ٣ إلى ٥ أيام')
        ->and($faq[0]->text)->toContain('تغليف هدايا')
        ->and(app(TenantContext::class)->run($this->other->id, fn () => app(KnowledgeRetriever::class)->retrieve(KnowledgeKind::Shipping)))->toBe([]);
});
