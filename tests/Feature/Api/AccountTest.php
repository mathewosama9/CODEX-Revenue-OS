<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config(['app.frontend_url' => 'https://app.codexai.systems']);

    $this->team = Team::factory()->create();
    $this->owner = User::factory()->create(['name' => 'Owner']);
    $this->agent = User::factory()->create(['name' => 'Agent']);
    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($this->agent, ['role' => TeamRole::Agent->value]);
    $this->base = "/api/v1/teams/{$this->team->slug}";
});

it('sends the password reset link to the Next.js app in the request language', function () {
    Notification::fake();

    $this->postJson('/forgot-password', ['email' => $this->owner->email], ['Accept-Language' => 'en'])->assertOk();

    Notification::assertSentTo($this->owner, ResetPassword::class, function (ResetPassword $notification) {
        $url = $notification->toMail($this->owner)->actionUrl;

        return str_starts_with($url, 'https://app.codexai.systems/en/reset-password?token=')
            && str_contains($url, 'email='.urlencode($this->owner->email));
    });
});

it('answers validation errors in Arabic or English, following Accept-Language', function () {
    $this->actingAs($this->owner)
        ->patchJson('/api/v1/me/profile', ['name' => '', 'email' => $this->owner->email], ['Accept-Language' => 'ar'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.name.0', 'الاسم مطلوب.');

    $this->actingAs($this->owner)
        ->patchJson('/api/v1/me/profile', ['name' => '', 'email' => $this->owner->email], ['Accept-Language' => 'en'])
        ->assertJsonPath('errors.name.0', 'The name field is required.');
});

it('updates the profile and resets verification when the email changes', function () {
    $this->actingAs($this->owner)
        ->patchJson('/api/v1/me/profile', ['name' => 'New Name', 'email' => 'new@example.com'])
        ->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.email_verified', false);
});

it('changes the password only with the current one', function () {
    $this->actingAs($this->owner)
        ->putJson('/api/v1/me/password', ['current_password' => 'wrong', 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('current_password');

    $this->actingAs($this->owner)
        ->putJson('/api/v1/me/password', ['current_password' => 'password', 'password' => 'N3w-password!', 'password_confirmation' => 'N3w-password!'])
        ->assertNoContent();
});

it('lists members for everyone, but emails and invitations only for the owner', function () {
    $agentView = $this->actingAs($this->agent)->getJson("{$this->base}/members")->assertOk();

    expect($agentView->json('data.members'))->toHaveCount(2)
        ->and($agentView->json('data.members.0'))->not->toHaveKey('email')
        ->and($agentView->json('data.invitations'))->toBe([]);

    $ownerView = $this->actingAs($this->owner)->getJson("{$this->base}/members")->assertOk();

    expect($ownerView->json('data.members.0'))->toHaveKey('email');
});

it('lets the owner invite an agent, and the email links to the app', function () {
    Notification::fake();

    $this->actingAs($this->owner)
        ->postJson("{$this->base}/invitations", ['email' => 'new.agent@example.com', 'role' => 'agent'])
        ->assertOk()
        ->assertJsonPath('data.invitations.0.email', 'new.agent@example.com');

    Notification::assertSentOnDemand(TeamInvitationNotification::class, function (TeamInvitationNotification $notification) {
        return str_starts_with($notification->toMail(new stdClass)->actionUrl, 'https://app.codexai.systems/');
    });
});

it('never invites a second owner, and agents cannot invite', function () {
    $this->actingAs($this->owner)
        ->postJson("{$this->base}/invitations", ['email' => 'x@example.com', 'role' => 'owner'])
        ->assertJsonValidationErrors('role');

    $this->actingAs($this->agent)
        ->postJson("{$this->base}/invitations", ['email' => 'x@example.com', 'role' => 'agent'])
        ->assertForbidden();
});

it('cancels an invitation of this workspace only', function () {
    $other = Team::factory()->create();
    $foreign = TeamInvitation::query()->create(['team_id' => $other->id, 'email' => 'a@example.com', 'role' => TeamRole::Agent, 'invited_by' => $this->owner->id, 'expires_at' => now()->addDay()]);
    $own = TeamInvitation::query()->create(['team_id' => $this->team->id, 'email' => 'b@example.com', 'role' => TeamRole::Agent, 'invited_by' => $this->owner->id, 'expires_at' => now()->addDay()]);

    $this->actingAs($this->owner)->deleteJson("{$this->base}/invitations/{$foreign->id}")->assertNotFound();
    $this->actingAs($this->owner)->deleteJson("{$this->base}/invitations/{$own->id}")->assertOk();

    expect(TeamInvitation::query()->find($own->id))->toBeNull()
        ->and(TeamInvitation::query()->find($foreign->id))->not->toBeNull();
});

it('removes an agent but never the owner', function () {
    $this->actingAs($this->owner)->deleteJson("{$this->base}/members/{$this->owner->id}")->assertForbidden();
    $this->actingAs($this->owner)->deleteJson("{$this->base}/members/{$this->agent->id}")->assertOk();

    expect($this->agent->fresh()->belongsToTeam($this->team))->toBeFalse();
});

it('shows pending invitations in /me and accepts them', function () {
    $invitee = User::factory()->create(['email' => 'Invitee@example.com']);
    $invitation = TeamInvitation::query()->create(['team_id' => $this->team->id, 'email' => 'invitee@example.com', 'role' => TeamRole::Agent, 'invited_by' => $this->owner->id, 'expires_at' => now()->addDay()]);

    $this->actingAs($invitee)->getJson('/api/v1/me')->assertJsonPath('data.invitations.0.code', $invitation->code);

    $this->actingAs($this->agent)->postJson("/api/v1/invitations/{$invitation->code}/accept")->assertUnprocessable();

    $this->actingAs($invitee)
        ->postJson("/api/v1/invitations/{$invitation->code}/accept")
        ->assertOk()
        ->assertJsonPath('data.invitations', []);

    expect($invitee->fresh()->teamRole($this->team))->toBe(TeamRole::Agent);
});
