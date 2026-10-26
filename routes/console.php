<?php

use App\Models\Team;
use App\Models\TeamInvitation;
use App\Modules\Commerce\Jobs\ReconcileStore;
use App\Modules\Commerce\Jobs\RefreshStoreToken;
use App\Modules\Commerce\Jobs\SyncCatalog;
use App\Modules\Compliance\Jobs\PurgeExpiredData;
use App\Modules\Knowledge\Jobs\ReindexCatalog;
use App\Modules\Measurement\Jobs\WeeklyMeasurement;
use App\Modules\Recovery\ScheduledActionDispatcher;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Webhooks\WebhookEvent;
use App\Shared\Webhooks\WebhookStatus;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

/*
| Commerce (M02). Fan out one job per commerce tenant (Salla or WooCommerce);
| each job runs inside its own tenant and checks its own state. Token refresh
| stays Salla only: WooCommerce keys never expire.
*/
Schedule::call(function () {
    foreach (TenantRoute::commerceTenants() as $tenantId) {
        ReconcileStore::dispatch($tenantId);
    }
})->everyTenMinutes()->name('commerce:reconcile')->withoutOverlapping()
    ->description('Poll the store platform for carts and orders whose webhooks were lost');

Schedule::call(function () {
    foreach (TenantRoute::commerceTenants() as $tenantId) {
        SyncCatalog::dispatch($tenantId);
    }
})->everyTenMinutes()->name('commerce:sync-catalog')->withoutOverlapping()
    ->description('Full product pass so the agent never quotes a price older than 15 minutes');

Schedule::call(function () {
    foreach (TenantRoute::commerceTenants() as $tenantId) {
        ReindexCatalog::dispatch($tenantId);
    }
})->dailyAt('00:30')->name('knowledge:reindex')->withoutOverlapping()
    ->description('Nightly safety net for the product search index (09 §9.12)');

Schedule::call(function () {
    foreach (TenantRoute::tenantsFor(TenantRoute::SALLA) as $tenantId) {
        RefreshStoreToken::dispatch($tenantId);
    }
})->hourly()->name('commerce:refresh-tokens')->withoutOverlapping()
    ->description('Refresh Salla tokens that expire within 72 hours');

/*
| Recovery (M08): claim due scheduled actions every minute (ADR-010).
*/
Schedule::call(fn () => app(ScheduledActionDispatcher::class)->dispatchDue())
    ->everyMinute()->name('recovery:dispatch')->withoutOverlapping()
    ->description('Claim and run due recovery steps');

/*
| Measurement (M09): weekly SRM check + frozen report snapshot per tenant
| (Sunday 06:00 in the workspace's own timezone, before the weekly merchant
| call, ADR-012). Checked hourly; each tenant is due in one hour a week.
*/
Schedule::call(function () {
    $tenantIds = TenantRoute::commerceTenants();
    $timezones = Team::query()->whereKey($tenantIds)->pluck('timezone', 'id');

    foreach ($tenantIds as $tenantId) {
        $local = now((string) ($timezones[$tenantId] ?? config('currencies.default_timezone')));

        if ($local->dayOfWeek === 0 && $local->hour === 6) {
            WeeklyMeasurement::dispatch($tenantId);
        }
    }
})->hourly()->name('measurement:weekly')->withoutOverlapping()
    ->description('SRM check and weekly profit report snapshot');

/*
| Compliance (M10): daily retention. Raw webhook payloads (platform-level,
| PII) go first; per-tenant message text and notes follow.
*/
Schedule::call(function () {
    WebhookEvent::query()
        ->where('received_at', '<', now()->subDays((int) config('codex.retention.webhook_payload_days')))
        // Failed ones too: retries end within the hour, and their payloads hold PII (review H5).
        ->whereIn('status', [WebhookStatus::Processed, WebhookStatus::Unhandled, WebhookStatus::Failed])
        ->delete();

    foreach (array_unique([...TenantRoute::commerceTenants(), ...TenantRoute::tenantsFor(TenantRoute::META)]) as $tenantId) {
        PurgeExpiredData::dispatch($tenantId);
    }
})->dailyAt('01:30')->name('compliance:retention')->withoutOverlapping()
    ->description('Delete data past its retention window');
