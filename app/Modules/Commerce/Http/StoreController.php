<?php

namespace App\Modules\Commerce\Http;

use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\StoreStatus;
use Illuminate\Http\JsonResponse;

/**
 * The workspace's store for the onboarding screen: which platform and site.
 * Never a token, key or secret.
 */
class StoreController
{
    public function show(): JsonResponse
    {
        $store = StoreConnection::query()->where('status', StoreStatus::Active)->first()
            ?? StoreConnection::query()->latest('updated_at')->first();

        return response()->json(['data' => [
            'connected' => $store !== null && $store->status === StoreStatus::Active,
            'store' => $store === null ? null : [
                'provider' => $store->provider,
                'store_id' => $store->external_store_id,
                'site_url' => $store->site_url,
                'name' => $store->store_name,
                'status' => $store->status->value,
                'last_webhook_at' => $store->last_webhook_at?->toIso8601String(),
            ],
        ]]);
    }
}
