<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\MeController;
use App\Http\Middleware\EnsureTeamMembership;
use App\Shared\Tenancy\SetTenantContext;
use Illuminate\Support\Facades\Route;

/*
| JSON API for the Next.js app (ADR-002). Sanctum SPA auth (session cookie).
| Tenant routes live under /api/v1/teams/{current_team}/… and always pass
| through the membership check, then the tenant context.
*/

Route::prefix('v1')->middleware(['auth:sanctum'])->name('api.')->group(function () {
    Route::get('me', MeController::class)->name('me');
    Route::patch('me/profile', [AccountController::class, 'updateProfile'])->name('me.profile');
    Route::put('me/password', [AccountController::class, 'updatePassword'])->middleware('throttle:6,1')->name('me.password');
    Route::post('invitations/{invitation}/accept', [AccountController::class, 'acceptInvitation'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [AccountController::class, 'declineInvitation'])->name('invitations.decline');

    Route::prefix('teams/{current_team}')
        ->middleware([EnsureTeamMembership::class, SetTenantContext::class])
        ->group(function () {
            require __DIR__.'/api/tenant.php';
        });
});
