<?php

namespace App\Modules\Commerce;

use App\Models\Team;
use App\Modules\Commerce\Data\TokenSet;
use App\Modules\Commerce\Events\StoreConnected;
use App\Modules\Commerce\Events\StoreDisconnected;
use App\Modules\Commerce\Jobs\BackfillStore;
use App\Modules\Commerce\Jobs\FetchStoreProfile;
use App\Modules\Commerce\Models\StoreConnection;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;

/**
 * Store install / re-authorize / uninstall (US-E02-01). Installing the same
 * store again never creates a second workspace.
 */
class StoreConnections
{
    public function __construct(
        private readonly TenantContext $tenants,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return int the tenant id the store belongs to
     */
    public function authorize(string $provider, string $externalStoreId, TokenSet $tokens): int
    {
        $tenantId = TenantRoute::tenantFor($provider, $externalStoreId);
        $isNew = $tenantId === null;

        $tenantId ??= DB::transaction(function () use ($provider, $externalStoreId) {
            $team = Team::create(['name' => "Salla {$externalStoreId}", 'is_personal' => false]);
            TenantRoute::bind($provider, $externalStoreId, $team->id);

            return $team->id;
        });

        $this->tenants->run($tenantId, function () use ($provider, $externalStoreId, $tokens, $isNew) {
            $connection = StoreConnection::query()->updateOrCreate(['provider' => $provider], [
                'external_store_id' => $externalStoreId,
                'access_token' => $tokens->accessToken,
                'refresh_token' => $tokens->refreshToken,
                'access_expires_at' => $tokens->expiresAt,
                'scope' => $tokens->scope,
                'status' => StoreStatus::Active,
            ]);

            $this->audit->record($isNew ? 'store.connected' : 'store.reauthorized', ActorType::System, subject: $connection);

            StoreConnected::dispatch($connection->tenant_id, $connection->id);

            FetchStoreProfile::dispatch($connection->tenant_id)->afterCommit();

            if ($isNew) {
                BackfillStore::dispatch($connection->tenant_id)->afterCommit();
            }
        });

        return $tenantId;
    }

    /**
     * Stop everything and drop the tokens; data follows the retention policy.
     */
    public function uninstall(string $provider): void
    {
        $connection = StoreConnection::query()->where('provider', $provider)->first();

        if ($connection === null) {
            return;
        }

        $connection->update([
            'status' => StoreStatus::Uninstalled,
            'access_token' => null,
            'refresh_token' => null,
        ]);

        $this->audit->record('store.uninstalled', ActorType::System, subject: $connection);

        StoreDisconnected::dispatch($connection->tenant_id, $connection->id);
    }

    public function markNeedsReauth(StoreConnection $connection, string $reason): void
    {
        $connection->update(['status' => StoreStatus::NeedsReauth]);

        $this->audit->record('store.needs_reauth', ActorType::System, subject: $connection, context: ['reason' => $reason]);

        StoreDisconnected::dispatch($connection->tenant_id, $connection->id);
    }
}
