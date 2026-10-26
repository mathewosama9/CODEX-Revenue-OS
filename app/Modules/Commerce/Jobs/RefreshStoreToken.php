<?php

namespace App\Modules\Commerce\Jobs;

use App\Modules\Commerce\CommerceAdapters;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Commerce\StoreConnections;
use App\Modules\Commerce\StoreStatus;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Tenancy\TenantAware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Salla refresh tokens are single use: a second refresh with the same token —
 * even in parallel — revokes everything and forces a reinstall. So: one lock
 * per store, re-read inside the lock, save the new pair before releasing it,
 * and never retry blindly (US-E02-02). Salla only: WooCommerce keys never
 * expire.
 */
class RefreshStoreToken implements ShouldQueue
{
    use Dispatchable, Queueable, TenantAware;

    public int $tries = 1;

    public const REFRESH_WITHIN_HOURS = 72;

    public function __construct(public int $tenantId) {}

    public function handle(CommerceAdapters $adapters, StoreConnections $connections): void
    {
        Cache::lock("commerce:token-refresh:{$this->tenantId}", 60)->get(function () use ($adapters, $connections) {
            $store = StoreConnection::query()
                ->where('provider', TenantRoute::SALLA)
                ->where('status', StoreStatus::Active)
                ->first();

            // Someone refreshed while we waited, or the store is gone.
            if ($store === null || $store->access_expires_at?->isAfter(now()->addHours(self::REFRESH_WITHIN_HOURS))) {
                return;
            }

            try {
                $tokens = $adapters->for($store)->refreshTokens($store);
            } catch (Throwable $exception) {
                $connections->markNeedsReauth($store, 'token_refresh_failed: '.$exception::class);

                return;
            }

            $store->update([
                'access_token' => $tokens->accessToken,
                'refresh_token' => $tokens->refreshToken,
                'access_expires_at' => $tokens->expiresAt,
            ]);
        });
    }
}
