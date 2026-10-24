<?php

use App\Enums\TeamPermission;
use App\Http\Middleware\EnsureTeamPermission;
use App\Modules\Measurement\Http\MarginsController;
use Illuminate\Support\Facades\Route;

/*
| Tenant-scoped API routes. Each module adds its group here.
| The tenant context is already set; use EnsureTeamPermission per route.
*/

Route::middleware(EnsureTeamPermission::using(TeamPermission::ManageMargins))->group(function () {
    Route::get('margins', [MarginsController::class, 'index'])->name('margins.index');
    Route::put('margins', [MarginsController::class, 'update'])->name('margins.update');
});
