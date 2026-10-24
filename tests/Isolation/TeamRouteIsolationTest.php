<?php

use App\Models\Team;
use App\Models\User;

it('forbids opening another tenant workspace by its url', function () {
    $user = User::factory()->create();
    $otherTeam = Team::factory()->create();

    $this->actingAs($user)
        ->get("/{$otherTeam->slug}/dashboard")
        ->assertForbidden();
});

it('lets a member open their own workspace', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam ?? $user->teams()->first();

    $this->actingAs($user)
        ->get("/{$team->slug}/dashboard")
        ->assertOk();
});
