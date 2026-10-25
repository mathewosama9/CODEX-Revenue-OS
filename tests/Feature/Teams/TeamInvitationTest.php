<?php

namespace Tests\Feature\Teams;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_invitations_can_be_created()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $response = $this
            ->actingAs($owner)
            ->postJson("/api/v1/teams/{$team->slug}/invitations", [
                'email' => 'invited@example.com',
                'role' => TeamRole::Agent->value,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('team_invitations', [
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => TeamRole::Agent->value,
        ]);
    }

    public function test_invitation_email_for_existing_users_uses_login_route()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => $invitedUser->email,
            'invited_by' => $owner->id,
        ]);

        $mail = (new TeamInvitationNotification($invitation))->toMail($invitedUser);

        // The Next.js app lists the invitation after sign-in (ADR-002).
        $this->assertSame(config('app.frontend_url').'/'.app()->getLocale().'/login', $mail->actionUrl);
        $this->assertStringContainsString('accept', implode(' ', $mail->introLines));
    }

    public function test_invitation_email_for_unknown_users_uses_login_route()
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'unknown@example.com',
            'invited_by' => $owner->id,
        ]);

        $mail = (new TeamInvitationNotification($invitation))->toMail((object) []);

        // The Next.js app lists the invitation after sign-in (ADR-002).
        $this->assertSame(config('app.frontend_url').'/'.app()->getLocale().'/login', $mail->actionUrl);
        $this->assertStringContainsString('log in', strtolower(implode(' ', $mail->introLines)));
    }

    public function test_team_invitations_cannot_be_created_by_agents()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $agent = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($agent, ['role' => TeamRole::Agent->value]);

        $response = $this
            ->actingAs($agent)
            ->postJson("/api/v1/teams/{$team->slug}/invitations", [
                'email' => 'invited@example.com',
                'role' => TeamRole::Agent->value,
            ]);

        $response->assertForbidden();
    }

    public function test_existing_team_members_cannot_be_invited()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $member = User::factory()->create(['email' => 'member@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Agent->value]);

        $response = $this
            ->actingAs($owner)
            ->postJson("/api/v1/teams/{$team->slug}/invitations", [
                'email' => 'member@example.com',
                'role' => TeamRole::Agent->value,
            ]);

        $response->assertJsonValidationErrors('email');
    }

    public function test_duplicate_invitations_cannot_be_created()
    {
        Notification::fake();

        $owner = User::factory()->create();
        $team = Team::factory()->create();
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($owner)
            ->postJson("/api/v1/teams/{$team->slug}/invitations", [
                'email' => 'invited@example.com',
                'role' => TeamRole::Agent->value,
            ]);

        $response->assertJsonValidationErrors('email');
    }

    public function test_team_invitations_cannot_be_created_by_members()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($member, ['role' => TeamRole::Agent->value]);

        $response = $this
            ->actingAs($member)
            ->postJson("/api/v1/teams/{$team->slug}/invitations", [
                'email' => 'invited@example.com',
                'role' => TeamRole::Agent->value,
            ]);

        $response->assertForbidden();
    }

    public function test_team_invitations_can_be_cancelled_by_owners()
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($owner)
            ->deleteJson("/api/v1/teams/{$team->slug}/invitations/{$invitation->id}");

        $response->assertOk();

        $this->assertDatabaseMissing('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_team_invitations_can_be_accepted()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'role' => TeamRole::Agent,
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($invitedUser)
            ->postJson("/api/v1/invitations/{$invitation->code}/accept");

        $response->assertOk();

        $this->assertTrue($invitedUser->fresh()->belongsToTeam($team));
        $this->assertNotNull($invitation->fresh()->accepted_at);
    }

    public function test_team_invitations_can_be_declined_by_the_invited_user()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($invitedUser)
            ->deleteJson("/api/v1/invitations/{$invitation->code}");

        $response->assertOk();

        $this->assertDatabaseMissing('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_team_invitations_cannot_be_declined_by_uninvited_user()
    {
        $owner = User::factory()->create();
        $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($uninvitedUser)
            ->deleteJson("/api/v1/invitations/{$invitation->code}");

        $response->assertJsonValidationErrors('invitation');

        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_accepted_team_invitations_cannot_be_declined()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->accepted()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($invitedUser)
            ->deleteJson("/api/v1/invitations/{$invitation->code}");

        $response->assertJsonValidationErrors('invitation');

        $this->assertDatabaseHas('team_invitations', [
            'id' => $invitation->id,
        ]);
    }

    public function test_team_invitations_cannot_be_accepted_by_uninvited_user()
    {
        $owner = User::factory()->create();
        $uninvitedUser = User::factory()->create(['email' => 'uninvited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($uninvitedUser)
            ->postJson("/api/v1/invitations/{$invitation->code}/accept");

        $response->assertJsonValidationErrors('invitation');

        $this->assertFalse($uninvitedUser->fresh()->belongsToTeam($team));
    }

    public function test_expired_invitations_cannot_be_accepted()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
        $team = Team::factory()->create();

        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

        $invitation = TeamInvitation::factory()->expired()->create([
            'team_id' => $team->id,
            'email' => 'invited@example.com',
            'invited_by' => $owner->id,
        ]);

        $response = $this
            ->actingAs($invitedUser)
            ->postJson("/api/v1/invitations/{$invitation->code}/accept");

        $response->assertJsonValidationErrors('invitation');

        $this->assertFalse($invitedUser->fresh()->belongsToTeam($team));
    }
}
