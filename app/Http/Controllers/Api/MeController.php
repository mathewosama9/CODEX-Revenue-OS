<?php

namespace App\Http\Controllers\Api;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Who am I, which workspaces can I open, and what may I do in each.
 */
class MeController
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'current_team' => $user->currentTeam?->slug,
                'teams' => $user->teams->map(fn (Team $team) => [
                    'slug' => $team->slug,
                    'name' => $team->name,
                    'role' => $user->teamRole($team)?->value,
                    'permissions' => collect(TeamPermission::cases())
                        ->filter(fn (TeamPermission $permission) => $user->hasTeamPermission($team, $permission))
                        ->map(fn (TeamPermission $permission) => $permission->value)
                        ->values(),
                ])->values(),
            ],
        ]);
    }
}
