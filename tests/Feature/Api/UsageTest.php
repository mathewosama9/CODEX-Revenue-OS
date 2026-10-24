<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Billing\UsageMeter;
use App\Shared\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('counts per day atomically and exports the month as JSON or CSV', function () {
    $team = Team::factory()->create();
    $owner = User::factory()->create();
    $agent = User::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($agent, ['role' => TeamRole::Agent->value]);

    app(TenantContext::class)->run($team->id, function () {
        $meter = app(UsageMeter::class);
        $meter->increment(UsageMeter::ELIGIBLE_CARTS, at: CarbonImmutable::parse('2026-11-03 10:00'));
        $meter->increment(UsageMeter::ELIGIBLE_CARTS, 4, CarbonImmutable::parse('2026-11-20 10:00'));
        // 22:30 UTC on Nov 30 is already Dec 1 in Riyadh.
        $meter->increment(UsageMeter::ELIGIBLE_CARTS, at: CarbonImmutable::parse('2026-11-30 22:30', 'UTC'));
    });

    $url = "/api/v1/teams/{$team->slug}/billing/usage?month=2026-11";

    $this->actingAs($owner)->getJson($url)->assertOk()->assertJsonPath('data.usage.eligible_carts', 5);
    expect($this->actingAs($owner)->get($url.'&format=csv')->streamedContent())->toContain('2026-11,eligible_carts,5');
    $this->actingAs($agent)->getJson($url)->assertForbidden();
});
