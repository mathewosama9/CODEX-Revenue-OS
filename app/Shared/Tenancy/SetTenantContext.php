<?php

namespace App\Shared\Tenancy;

use App\Models\Team;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the tenant from the route's team. Must run after EnsureTeamMembership,
 * so the tenant is only ever a team the user belongs to.
 */
class SetTenantContext
{
    public function __construct(private TenantContext $tenants) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $team = $request->route('current_team') ?? $request->route('team');

        if (is_string($team)) {
            $team = Team::where('slug', $team)->first();
        }

        abort_unless($team instanceof Team, 404);

        $this->tenants->set($team->id);

        try {
            return $next($request);
        } finally {
            $this->tenants->forget();
        }
    }
}
