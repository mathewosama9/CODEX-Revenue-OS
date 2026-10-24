<?php

namespace App\Http\Middleware;

use App\Enums\TeamPermission;
use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level permission check against the MVP matrix (09 §9.13).
 * Usage: ->middleware(EnsureTeamPermission::using(TeamPermission::ManageMargins)).
 * Must run after EnsureTeamMembership.
 */
class EnsureTeamPermission
{
    public static function using(TeamPermission $permission): string
    {
        return static::class.':'.$permission->value;
    }

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $team = $request->route('current_team');

        if (is_string($team)) {
            $team = Team::where('slug', $team)->first();
        }

        $user = $request->user();

        abort_unless(
            $user !== null
            && $team instanceof Team
            && $user->hasTeamPermission($team, TeamPermission::from($permission)),
            403,
        );

        return $next($request);
    }
}
