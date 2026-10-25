<?php

use App\Enums\TeamPermission;
use App\Http\Controllers\Api\TeamMembersController;
use App\Http\Middleware\EnsureTeamPermission;
use App\Modules\Billing\Http\UsageController;
use App\Modules\Compliance\Http\ConsentImportController;
use App\Modules\Compliance\Http\DataRequestController;
use App\Modules\Inbox\Http\InboxController;
use App\Modules\Knowledge\Http\KnowledgeController;
use App\Modules\Measurement\Http\MarginsController;
use App\Modules\Measurement\Http\ProfitReportController;
use App\Modules\Messaging\Http\WhatsAppController;
use App\Modules\Recovery\Http\RecoveryController;
use App\Modules\SalesAgent\Http\AgentController;
use App\Modules\SalesAgent\Http\DraftsController;
use Illuminate\Support\Facades\Route;

/*
| Tenant-scoped API routes. Each module adds its group here.
| The tenant context is already set; use EnsureTeamPermission per route.
*/

Route::get('members', [TeamMembersController::class, 'index'])->name('members.index');
Route::post('invitations', [TeamMembersController::class, 'invite'])
    ->middleware(EnsureTeamPermission::using(TeamPermission::CreateInvitation))
    ->name('team-invitations.store');
Route::delete('invitations/{invitation}', [TeamMembersController::class, 'cancelInvitation'])
    ->whereNumber('invitation')
    ->middleware(EnsureTeamPermission::using(TeamPermission::CancelInvitation))
    ->name('team-invitations.destroy');
Route::delete('members/{user}', [TeamMembersController::class, 'remove'])
    ->whereNumber('user')
    ->middleware(EnsureTeamPermission::using(TeamPermission::RemoveMember))
    ->name('members.destroy');

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
    });

    // The kill switch belongs to everyone who may stop the AI, agents included (US-E14-04, review H7).
    Route::middleware(EnsureTeamPermission::using(TeamPermission::UseKillSwitch))->group(function () {
        Route::get('status', [RecoveryController::class, 'status'])->name('recovery.status');
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
    Route::post('{conversation}/takeover', [InboxController::class, 'takeover'])->whereNumber('conversation')->name('inbox.takeover');
    Route::post('{conversation}/hand-back', [InboxController::class, 'handBack'])->whereNumber('conversation')->name('inbox.hand-back');
    Route::get('{conversation}/ai', [DraftsController::class, 'activity'])->whereNumber('conversation')->name('inbox.ai');
});

Route::prefix('ai')->group(function () {
    Route::middleware(EnsureTeamPermission::using(TeamPermission::ReviewAiDrafts))->group(function () {
        Route::get('drafts', [DraftsController::class, 'index'])->name('ai.drafts.index');
        Route::post('drafts/{draft}/approve', [DraftsController::class, 'approve'])->whereNumber('draft')->name('ai.drafts.approve');
        Route::post('drafts/{draft}/reject', [DraftsController::class, 'reject'])->whereNumber('draft')->name('ai.drafts.reject');
    });

    Route::middleware(EnsureTeamPermission::using(TeamPermission::ManageAutonomy))->group(function () {
        Route::get('settings', [AgentController::class, 'show'])->name('ai.settings.show');
        Route::put('settings', [AgentController::class, 'update'])->name('ai.settings.update');
    });

    Route::post('stop', [AgentController::class, 'stop'])
        ->middleware(EnsureTeamPermission::using(TeamPermission::UseKillSwitch))
        ->name('ai.stop');
});

Route::prefix('knowledge')->middleware(EnsureTeamPermission::using(TeamPermission::ManageKnowledge))->group(function () {
    Route::get('/', [KnowledgeController::class, 'index'])->name('knowledge.index');
    Route::post('/', [KnowledgeController::class, 'store'])->name('knowledge.store');
    Route::put('{document}', [KnowledgeController::class, 'update'])->whereNumber('document')->name('knowledge.update');
    Route::delete('{document}', [KnowledgeController::class, 'destroy'])->whereNumber('document')->name('knowledge.destroy');
});

Route::get('billing/usage', UsageController::class)
    ->middleware(EnsureTeamPermission::using(TeamPermission::ManageBilling))
    ->name('billing.usage');

Route::post('data-requests', DataRequestController::class)
    ->middleware(EnsureTeamPermission::using(TeamPermission::ManageDataRequests))
    ->name('data-requests');

Route::prefix('consent/imports')->middleware(EnsureTeamPermission::using(TeamPermission::ImportConsent))->group(function () {
    Route::get('/', [ConsentImportController::class, 'index'])->name('consent.imports.index');
    Route::post('/', [ConsentImportController::class, 'store'])->middleware('throttle:10,1')->name('consent.imports.store');
});
