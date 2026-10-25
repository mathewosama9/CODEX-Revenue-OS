<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('requires authentication', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

it('returns teams with role and permissions', function () {
    $owner = User::factory()->create();
    $agent = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $team->members()->attach($agent, ['role' => TeamRole::Agent->value]);

    $agentTeam = collect($this->actingAs($agent)->getJson('/api/v1/me')->assertOk()->json('data.teams'))
        ->firstWhere('slug', $team->slug);

    expect($agentTeam['role'])->toBe('agent')
        ->and($agentTeam['permissions'])->toContain('inbox:use')
        ->and($agentTeam['permissions'])->not->toContain('discounts:manage');

    $ownerTeam = collect($this->actingAs($owner)->getJson('/api/v1/me')->json('data.teams'))
        ->firstWhere('slug', $team->slug);

    expect($ownerTeam['permissions'])->toContain('discounts:manage');
});

it('never changes the current workspace just by reading another one (review H12)', function () {
    $user = User::factory()->create();
    $first = Team::factory()->create();
    $second = Team::factory()->create();
    $first->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $second->members()->attach($user, ['role' => TeamRole::Owner->value]);
    $user->switchTeam($first);

    $this->actingAs($user)->getJson("/api/v1/teams/{$second->slug}/members")->assertOk();

    expect($user->fresh()->current_team_id)->toBe($first->id);
});
