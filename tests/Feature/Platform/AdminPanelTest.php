<?php

use App\Models\Team;
use App\Models\User;
use App\Modules\Platform\Filament\Pages\TenantHealth;
use App\Modules\Platform\Models\Admin;
use App\Modules\Platform\PlatformHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function adminWith2fa(): Admin
{
    return Admin::query()->create([
        'name' => 'Ops', 'email' => 'ops@codexai.systems', 'password' => 'secret-password-123',
        'app_authentication_secret' => 'JBSWY3DPEHPK3PXP',
    ]);
}

it('sends guests to the admin login', function () {
    $this->get('/admin/tenant-health')->assertRedirect('/admin/login');
});

it('never lets a merchant session into the admin', function () {
    $this->actingAs(User::factory()->create())->get('/admin/tenant-health')->assertRedirect('/admin/login');
});

it('forces 2FA setup before anything else', function () {
    $admin = Admin::query()->create(['name' => 'New', 'email' => 'new@codexai.systems', 'password' => 'secret-password-123']);

    $this->actingAs($admin, 'admin')->get('/admin/tenant-health')->assertRedirectContains('multi-factor-authentication/set-up');
});

it('shows every store with its health level', function () {
    $team = Team::factory()->create(['name' => 'عطور الريم']);
    DB::table('store_connections')->insert(['tenant_id' => $team->id, 'provider' => 'salla', 'external_store_id' => 'm', 'status' => 'needs_reauth']);

    expect(app(PlatformHealth::class)->forTenant($team->id)['level'])->toBe('red');

    $this->actingAs(adminWith2fa(), 'admin')->get('/admin/tenant-health')->assertOk()->assertSee('عطور الريم');
});

it('pauses a store from the admin, with a reason in the audit log', function () {
    $team = Team::factory()->create();
    DB::table('recovery_settings')->insert(['tenant_id' => $team->id, 'enabled' => true]);

    $this->actingAs(adminWith2fa(), 'admin');

    Livewire::test(TenantHealth::class)
        ->callTableAction('pause', $team, data: ['reason' => 'جودة الرقم نزلت'])
        ->assertHasNoTableActionErrors();

    expect(DB::table('recovery_settings')->value('enabled'))->toBeFalse();

    $audit = DB::table('audit_events')->where('action', 'recovery.paused')->first();
    expect($audit->actor_type)->toBe('admin')
        ->and(json_decode($audit->context, true)['reason'])->toBe('admin: جودة الرقم نزلت');
});

it('reads each store health once per row, not once per column (review H14)', function () {
    foreach (range(1, 3) as $i) {
        Team::factory()->create(['name' => "متجر {$i}"]);
    }

    $this->actingAs(adminWith2fa(), 'admin');

    DB::enableQueryLog();
    Livewire::test(TenantHealth::class)->assertOk();
    $healthReads = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'from "store_connections"'))->count();

    // One store_connections read per store row (3), not one per column (27).
    expect($healthReads)->toBeLessThanOrEqual(3);
});
