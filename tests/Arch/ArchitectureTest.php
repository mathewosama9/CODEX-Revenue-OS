<?php

use App\Modules\SalesAgent\Toolbox;
use App\Shared\Tenancy\BelongsToTenant;
use Symfony\Component\Finder\Finder;

const MODULES = [
    'Identity', 'Commerce', 'Messaging', 'Inbox', 'Recovery',
    'Measurement', 'Compliance', 'Billing', 'Platform',
    'AiGateway', 'Knowledge', 'SalesAgent',
];

/** Cross-tenant tables (ADR-009). Each entry needs an ADR reference. */
const TENANTLESS_MODELS = [
    'App\Modules\Platform\Models\Admin', // CODEX staff, platform level (E14)
    'App\Modules\AiGateway\Models\EvalRun', // golden-set results, written by CODEX, no merchant data (E10)
];

/** Modules allowed to bypass the tenant scope (09 §9.4.2). */
const SCOPE_BYPASS_ALLOWED = ['app/Modules/Platform', 'app/Modules/Measurement'];

// Arch tests run without booting Laravel, so paths are resolved from this file.
function appDir(string $path = ''): string
{
    return dirname(__DIR__, 2).'/app'.($path === '' ? '' : '/'.$path);
}

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

arch('shared tenancy layer does not depend on modules')
    ->expect('App\Shared')
    ->not->toUse('App\Modules');

foreach (MODULES as $module) {
    arch("{$module} models stay inside {$module}")
        ->expect("App\\Modules\\{$module}\\Models")
        ->toOnlyBeUsedIn("App\\Modules\\{$module}");
}

arch('only the AiGateway talks to the AI SDK (US-E10-01)')
    ->expect('Laravel\Ai')
    ->toOnlyBeUsedIn('App\Modules\AiGateway');

arch('no other LLM client exists beside the gateway (US-E10-01)')
    ->expect(['Anthropic', 'OpenAI', 'Prism', 'Gemini'])
    ->not->toBeUsed();

it('sends no HTTP request to an AI provider outside the gateway (US-E10-01)', function () {
    $hosts = '/anthropic\.com|openai\.com|voyageai\.com|generativelanguage\.googleapis|api\.mistral|api\.cohere/i';
    $files = Finder::create()->files()->name('*.php')->in(appDir())->notPath('Modules/AiGateway');

    foreach ($files as $file) {
        expect(preg_match($hosts, $file->getContents()))->toBe(0, "app/{$file->getRelativePathname()} names an AI provider host.");
    }
});

arch('agent tools never move money or change orders (Rule 9)')
    ->expect('App\Modules\SalesAgent\Tools')
    ->not->toUse([
        'App\Modules\Recovery',
        'App\Modules\Messaging',
        'App\Modules\Commerce\CommerceAdapter',
        'App\Modules\Commerce\CommerceRecorder',
        'App\Modules\Commerce\Salla',
        'Illuminate\Support\Facades\Http',
        'Illuminate\Support\Facades\DB',
    ]);

it('gives the agent exactly five read-only tools, none for discounts, payments or orders (Rule 9)', function () {
    expect(Toolbox::ALLOWED)->toBe(['search_products', 'get_product', 'get_policy', 'get_cart_link', 'handoff']);

    $files = Finder::create()->files()->name('*.php')->in(appDir('Modules/SalesAgent/Tools'));

    foreach ($files as $file) {
        expect(preg_match('/discount|coupon|voucher|payment|refund|order|خصم|كوبون/i', $file->getFilename()))->toBe(0, "{$file->getFilename()} looks like a money tool.");
    }
});

arch('measurement never reads message content (ADR-009)')
    ->expect('App\Modules\Measurement')
    ->not->toUse(['App\Modules\Messaging', 'App\Modules\Inbox']);

it('puts every module model behind the tenant scope', function () {
    $files = Finder::create()->files()->name('*.php')->in(appDir('Modules'))->path('/Models/');

    foreach ($files as $file) {
        $class = 'App\\Modules\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());

        if (in_array($class, TENANTLESS_MODELS, true)) {
            continue;
        }

        expect(in_array(BelongsToTenant::class, class_uses_recursive($class), true))
            ->toBeTrue("{$class} is tenant-owned and must use BelongsToTenant.");
    }

    expect(true)->toBeTrue();
});

it('only bypasses the tenant scope where allowed', function () {
    $files = Finder::create()->files()->name('*.php')->in(appDir())->contains('withoutGlobalScope');

    foreach ($files as $file) {
        $relative = 'app/'.$file->getRelativePathname();

        $allowed = collect(SCOPE_BYPASS_ALLOWED)->contains(fn ($prefix) => str_starts_with($relative, $prefix));

        expect($allowed)->toBeTrue("{$relative} bypasses the tenant scope outside Platform/Measurement.");
    }

    expect(true)->toBeTrue();
});
