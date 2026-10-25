<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Measurement\MarginLookup;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->owner = User::factory()->create();
    $this->agent = User::factory()->create();
    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($this->agent, ['role' => TeamRole::Agent->value]);
    DB::table('categories')->insert([
        ['tenant_id' => $this->team->id, 'external_id' => 'c1', 'name' => 'عطور'],
        ['tenant_id' => $this->team->id, 'external_id' => 'c2', 'name' => 'بخور'],
    ]);
});

it('lets the owner set margins per category and audits it', function () {
    $this->actingAs($this->owner)
        ->putJson("/api/v1/teams/{$this->team->slug}/margins", [
            'default_margin_bps' => 3500,
            'categories' => [['external_id' => 'c1', 'margin_bps' => 4500]],
        ])
        ->assertOk()
        ->assertJsonPath('data.default.margin_bps', 3500)
        ->assertJsonPath('data.categories.0.margin_bps', null)
        ->assertJsonPath('data.categories.1.margin_bps', 4500);

    expect(DB::table('audit_events')->where('action', 'margins.updated')->exists())->toBeTrue();
});

it('forbids agents from reading or changing margins', function () {
    $this->actingAs($this->agent)->getJson("/api/v1/teams/{$this->team->slug}/margins")->assertForbidden();
    $this->actingAs($this->agent)->putJson("/api/v1/teams/{$this->team->slug}/margins", ['default_margin_bps' => 1])->assertForbidden();
});

it('falls back from category to store default to the assumed margin', function () {
    $lookup = app(MarginLookup::class);

    app(TenantContext::class)->run($this->team->id, function () use ($lookup) {
        expect($lookup->resolve('c1'))->toBe(['bps' => 3000, 'source' => 'assumed']);

        $lookup->update(3500, ['c1' => 4500], null);

        expect($lookup->resolve('c1'))->toBe(['bps' => 4500, 'source' => 'category'])
            ->and($lookup->resolve('c2'))->toBe(['bps' => 3500, 'source' => 'store_default']);
    });
});

it('keeps old reports stable when a margin changes later', function () {
    $lookup = app(MarginLookup::class);

    app(TenantContext::class)->run($this->team->id, function () use ($lookup) {
        CarbonImmutable::setTestNow('2026-11-01 10:00');
        $lookup->update(null, ['c1' => 4000], null);

        CarbonImmutable::setTestNow('2026-11-20 10:00');
        $lookup->update(null, ['c1' => 2000], null);

        expect($lookup->marginBps('c1', CarbonImmutable::parse('2026-11-10')))->toBe(4000)
            ->and($lookup->marginBps('c1'))->toBe(2000);

        CarbonImmutable::setTestNow();
    });
});

it('rejects impossible margins', function () {
    $this->actingAs($this->owner)
        ->putJson("/api/v1/teams/{$this->team->slug}/margins", ['default_margin_bps' => 12000])
        ->assertUnprocessable();
});

it('clears a category margin back to the store default, keeping its history (review H16)', function () {
    $url = "/api/v1/teams/{$this->team->slug}/margins";

    $this->actingAs($this->owner)->putJson($url, ['default_margin_bps' => 3000, 'categories' => [['external_id' => 'c1', 'margin_bps' => 4500]]])->assertOk();
    $this->actingAs($this->owner)->putJson($url, ['categories' => [['external_id' => 'c1', 'margin_bps' => null]]])
        ->assertOk()
        ->assertJsonPath('data.categories.1.margin_bps', null);

    expect(app(TenantContext::class)->run($this->team->id, fn () => app(MarginLookup::class)->resolve('c1')))->toBe(['bps' => 3000, 'source' => 'store_default'])
        ->and(DB::table('category_margins')->where('category_external_id', 'c1')->count())->toBe(2);
});
