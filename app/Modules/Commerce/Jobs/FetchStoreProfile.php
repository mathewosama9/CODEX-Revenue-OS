<?php

namespace App\Modules\Commerce\Jobs;

use App\Models\Team;
use App\Modules\Commerce\CommerceAdapters;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\StoreStatus;
use App\Shared\Tenancy\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Names the workspace after the store and keeps the owner's email so the
 * concierge onboarding (P1) can invite the Owner.
 */
class FetchStoreProfile implements ShouldQueue
{
    use Dispatchable, Queueable, TenantAware;

    public int $tries = 3;

    public function __construct(public int $tenantId) {}

    public function handle(CommerceAdapters $adapters): void
    {
        // The active store: a tenant that moved platforms keeps its old, uninstalled row.
        $store = StoreConnection::query()->where('status', StoreStatus::Active)->first();

        if ($store === null) {
            return;
        }

        $profile = $adapters->for($store)->storeProfile($store);

        $store->update(['store_name' => $profile['name'], 'owner_email' => $profile['email']]);

        if (is_string($profile['name']) && $profile['name'] !== '') {
            Team::query()->whereKey($this->tenantId)->update(['name' => $profile['name']]);
        }
    }
}
