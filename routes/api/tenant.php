<?php

use App\Enums\TeamPermission;
use App\Http\Middleware\EnsureTeamPermission;
use App\Modules\Billing\Http\UsageController;
use App\Modules\Inbox\Http\InboxController;
use App\Modules\Measurement\Http\MarginsController;
use App\Modules\Measurement\Http\ProfitReportController;
use App\Modules\Messaging\Http\WhatsAppController;
use App\Modules\Recovery\Http\RecoveryController;
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

Route::prefix('recovery')->group(function () {
    Route::middleware(EnsureTeamPermission::using(TeamPermission::ManageRecoverySettings))->group(function () {
        Route::get('/', [RecoveryController::class, 'show'])->name('recovery.show');
        Route::put('/', [RecoveryController::class, 'update'])->name('recovery.update');
        Route::post('enable', [RecoveryController::class, 'enable'])->name('recovery.enable');
        Route::post('pause', [RecoveryController::class, 'pause'])->name('recovery.pause');
    });

    Route::middleware(EnsureTeamPermission::using(TeamPermission::ManageDiscountPolicy))->group(function () {
        Route::get('approvals', [RecoveryController::class, 'approvals'])->name('recovery.approvals');
        Route::post('approvals/{approval}', [RecoveryController::class, 'decide'])->whereNumber('approval')->name('recovery.approvals.decide');
    });
});

Route::prefix('reports/profit')->middleware(EnsureTeamPermission::using(TeamPermission::ViewProfitReport))->group(function () {
    Route::get('/', [ProfitReportController::class, 'show'])->name('reports.profit');
    Route::get('snapshots', [ProfitReportController::class, 'snapshots'])->name('reports.profit.snapshots');
});

Route::prefix('inbox')->middleware(EnsureTeamPermission::using(TeamPermission::UseInbox))->group(function () {
    Route::get('/', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('{conversation}', [InboxController::class, 'show'])->whereNumber('conversation')->name('inbox.show');
    Route::patch('{conversation}', [InboxController::class, 'update'])->whereNumber('conversation')->name('inbox.update');
    Route::post('{conversation}/notes', [InboxController::class, 'note'])->whereNumber('conversation')->name('inbox.notes');
});

Route::get('billing/usage', UsageController::class)
    ->middleware(EnsureTeamPermission::using(TeamPermission::ManageBilling))
    ->name('billing.usage');
