<?php

namespace App\Http\Controllers\Api;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Http\Requests\Teams\RespondToTeamInvitationRequest;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The signed-in user's own settings for the Next.js app (ADR-002).
 * Two-factor setup uses Fortify's /user/* routes directly.
 */
class AccountController
{
    use PasswordValidationRules, ProfileValidationRules;

    public function updateProfile(Request $request, MeController $me): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->fill($request->validate($this->profileRules($user->id)));

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $me($request);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => $this->currentPasswordRules(),
            'password' => $this->passwordRules(),
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->update(['password' => $validated['password']]);

        return response()->json(null, 204);
    }

    public function acceptInvitation(RespondToTeamInvitationRequest $request, TeamInvitation $invitation, MeController $me): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        DB::transaction(function () use ($user, $invitation) {
            $team = $invitation->team;
            $team->memberships()->firstOrCreate(['user_id' => $user->id], ['role' => $invitation->role]);
            $invitation->update(['accepted_at' => now()]);
            $user->switchTeam($team);
        });

        return $me($request);
    }

    public function declineInvitation(RespondToTeamInvitationRequest $request, TeamInvitation $invitation, MeController $me): JsonResponse
    {
        $invitation->delete();

        return $me($request);
    }
}
