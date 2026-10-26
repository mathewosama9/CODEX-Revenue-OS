<?php

namespace App\Http\Controllers\Api;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Shared\Money\Currencies;
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
                'email_verified' => $user->hasVerifiedEmail(),
                'two_factor_enabled' => $user->hasEnabledTwoFactorAuthentication(),
                'current_team' => $user->currentTeam?->slug,
                'teams' => $user->teams->map(fn (Team $team) => [
                    'slug' => $team->slug,
                    'name' => $team->name,
                    'role' => $user->teamRole($team)?->value,
                    // ADR-012: money in this workspace is integer minor units of this currency.
                    'currency' => self::currency($team),
                    'timezone' => $team->timezone ?? (string) config('currencies.default_timezone'),
                    'permissions' => collect(TeamPermission::cases())
                        ->filter(fn (TeamPermission $permission) => $user->hasTeamPermission($team, $permission))
                        ->map(fn (TeamPermission $permission) => $permission->value)
                        ->values(),
                ])->values(),
                // Workspaces this email was invited to; accepted from the workspace picker.
                'invitations' => TeamInvitation::query()
                    ->with('team')
                    ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
                    ->whereNull('accepted_at')
                    ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->get()
                    ->map(fn (TeamInvitation $invitation) => [
                        // The code is the route key: invitation ids are guessable.
                        'code' => $invitation->code,
                        'team_name' => $invitation->team->name,
                        'role' => $invitation->role->value,
                    ])->values(),
            ],
        ]);
    }

    /**
     * @return array{code: string, exponent: int, symbol_ar: string, symbol_en: string}
     */
    private static function currency(Team $team): array
    {
        $currency = Currencies::get(Currencies::supports($team->currency) ? $team->currency : (string) config('currencies.default'));

        return [
            'code' => $currency->code,
            'exponent' => $currency->exponent,
            'symbol_ar' => $currency->symbol('ar'),
            'symbol_en' => $currency->symbol('en'),
        ];
    }
}
