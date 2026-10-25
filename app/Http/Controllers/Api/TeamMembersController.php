<?php

namespace App\Http\Controllers\Api;

use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\Teams\TeamInvitation as TeamInvitationNotification;
use App\Rules\UniqueTeamInvitation;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/**
 * Who is in the workspace. Every member sees names and roles (the inbox needs
 * them); emails and invitations only for those who may invite.
 */
class TeamMembersController
{
    public function __construct(private readonly TenantContext $tenants) {}

    public function index(Request $request): JsonResponse
    {
        $team = $this->team();
        /** @var User $viewer */
        $viewer = $request->user();
        $canInvite = $viewer->hasTeamPermission($team, TeamPermission::CreateInvitation);

        return response()->json(['data' => [
            'members' => $team->members()->orderBy('name')->get()->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'role' => $member->teamRole($team)?->value,
            ] + ($canInvite ? ['email' => $member->email] : []))->values(),
            'invitations' => $canInvite
                ? $team->invitations()->whereNull('accepted_at')
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->latest()->get()
                    ->map(fn (TeamInvitation $invitation) => [
                        'id' => $invitation->id,
                        'email' => $invitation->email,
                        'role' => $invitation->role->value,
                        'expires_at' => $invitation->expires_at?->toIso8601String(),
                    ])->values()
                : [],
        ]]);
    }

    public function invite(Request $request): JsonResponse
    {
        $team = $this->team();

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', new UniqueTeamInvitation($team)],
            // Owner is never invited (two roles in the MVP, spine §4.1).
            'role' => ['required', 'string', Rule::in(array_column(TeamRole::assignable(), 'value'))],
        ]);

        $invitation = $team->invitations()->create([
            'email' => $validated['email'],
            'role' => TeamRole::from($validated['role']),
            'invited_by' => $request->user()?->id,
            'expires_at' => now()->addDays(3),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify((new TeamInvitationNotification($invitation))->locale(app()->getLocale()));

        return $this->index($request);
    }

    public function cancelInvitation(Request $request): JsonResponse
    {
        $this->team()->invitations()->whereKey((int) $request->route('invitation'))->firstOrFail()->delete();

        return $this->index($request);
    }

    public function remove(Request $request): JsonResponse
    {
        $team = $this->team();
        $user = $team->members()->whereKey((int) $request->route('user'))->firstOrFail();

        abort_if($team->owner()?->is($user), 403, __('The team owner cannot be removed.'));

        $team->memberships()->where('user_id', $user->id)->delete();

        if ($user->isCurrentTeam($team) && ($personal = $user->personalTeam()) !== null) {
            $user->switchTeam($personal);
        }

        return $this->index($request);
    }

    private function team(): Team
    {
        return Team::query()->findOrFail($this->tenants->require());
    }
}
