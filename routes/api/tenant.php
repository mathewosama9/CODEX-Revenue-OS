<?php

use App\Enums\TeamPermission;
use App\Http\Middleware\EnsureTeamPermission;
use App\Modules\Measurement\Http\MarginsController;
use App\Modules\Messaging\Http\WhatsAppController;
use Illuminate\Support\Facades\Route;

/*
| Tenant-scoped API routes. Each module adds its group here.
| The tenant context is already set; use EnsureTeamPermission per route.
*/

Route::middleware(EnsureTeamPermission::using(TeamPermission::ManageMargins))->group(function () {
    Route::get('margins', [MarginsController::class, 'index'])->name('margins.index');
    Route::put('margins', [MarginsController::class, 'update'])->name('margins.update');
});

Route::prefix('whatsapp')->middleware(EnsureTeamPermission::using(TeamPermission::ManageIntegrations))->group(function () {
    Route::get('/', [WhatsAppController::class, 'show'])->name('whatsapp.show');
    Route::post('embedded-signup', [WhatsAppController::class, 'completeSignup'])->name('whatsapp.signup');
    Route::post('templates/submit-library', [WhatsAppController::class, 'submitTemplates'])->name('whatsapp.templates.submit');
    Route::post('templates/sync', [WhatsAppController::class, 'syncTemplates'])->name('whatsapp.templates.sync');
});
