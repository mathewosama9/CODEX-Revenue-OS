<?php

use App\Shared\Tenancy\BelongsToTenant;
use Symfony\Component\Finder\Finder;

const MODULES = [
    'Identity', 'Commerce', 'Messaging', 'Inbox', 'Recovery',
    'Measurement', 'Compliance', 'Billing', 'Platform',
];

/** Cross-tenant tables (ADR-009). Each entry needs an ADR reference. */
const TENANTLESS_MODELS = [
    // 'App\Modules\Measurement\Models\PooledOutcome', // ADR-009
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
