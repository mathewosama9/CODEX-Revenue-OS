<?php

use App\Models\Team;
use App\Models\User;

it('forbids opening another tenant workspace by its url', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $this->actingAs($user)
        // Every workspace route lives under the API now (ADR-002).
        ->getJson("/api/v1/teams/{$otherTeam->slug}/members")
        ->assertForbidden();
});

it('lets a member open their own workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam ?? $user->teams()->first();

    $this->actingAs($user)
        ->getJson("/api/v1/teams/{$team->slug}/members")
        ->assertOk();
});
