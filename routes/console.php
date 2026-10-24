<?php

use App\Models\TeamInvitation;
use App\Modules\Commerce\Jobs\ReconcileStore;
use App\Modules\Commerce\Jobs\RefreshStoreToken;
use App\Shared\Integrations\TenantRoute;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

/*
| Commerce (M02). Fan out one job per Salla tenant; each job runs inside its
| own tenant and checks its own state.
*/
Schedule::call(function () {
    foreach (TenantRoute::tenantsFor(TenantRoute::SALLA) as $tenantId) {
        ReconcileStore::dispatch($tenantId);
    }
})->everyTenMinutes()->name('commerce:reconcile')->withoutOverlapping()
    ->description('Poll Salla for carts and orders whose webhooks were lost');

Schedule::call(function () {
    foreach (TenantRoute::tenantsFor(TenantRoute::SALLA) as $tenantId) {
        RefreshStoreToken::dispatch($tenantId);
    }
})->hourly()->name('commerce:refresh-tokens')->withoutOverlapping()
    ->description('Refresh Salla tokens that expire within 72 hours');
