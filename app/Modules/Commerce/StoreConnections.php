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
 *
 * Two ways in: authorize() for a platform that installs from its own app
 * store (Salla) and creates the workspace, connect() for a store the owner
 * links from inside a workspace they already have (WooCommerce).
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
            // Salla stores sell in SAR on Riyadh time (ADR-012).
            $team = Team::create([
                'name' => self::platformName($provider)." {$externalStoreId}",
                'is_personal' => false,
                'currency' => 'SAR',
                'timezone' => 'Asia/Riyadh',
            ]);
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
     * Link a store to the current workspace (the owner is signed in, so the
     * workspace already has members). Refuses a store that belongs to another
     * workspace, and a second live store on another platform.
     *
     * @param  array<string, mixed>  $attributes  platform columns, e.g. site_url and credentials
     *
     * @throws StoreAlreadyConnected
     */
    public function connect(string $provider, string $externalStoreId, array $attributes, ?int $actorId = null): StoreConnection
    {
        $tenantId = $this->tenants->require();

        $owner = TenantRoute::tenantFor($provider, $externalStoreId);

        if ($owner !== null && $owner !== $tenantId) {
            throw StoreAlreadyConnected::toAnotherWorkspace($provider);
        }

        $other = StoreConnection::query()
            ->where('provider', '!=', $provider)
            ->where('status', StoreStatus::Active)
            ->value('provider');

        if (is_string($other)) {
            throw StoreAlreadyConnected::onAnotherPlatform($other);
        }

        $previous = StoreConnection::query()->where('provider', $provider)->first();
        $isNew = $previous === null
            || $previous->external_store_id !== $externalStoreId
            || $previous->status !== StoreStatus::Active;

        unset($attributes['tenant_id'], $attributes['provider'], $attributes['external_store_id'], $attributes['status']);

        $connection = DB::transaction(function () use ($provider, $externalStoreId, $attributes, $tenantId, $isNew, $actorId) {
            // One store per platform per workspace: a store replaced here stops routing to us.
            TenantRoute::query()
                ->where('provider', $provider)
                ->where('tenant_id', $tenantId)
                ->where('external_id', '!=', $externalStoreId)
                ->delete();

            TenantRoute::bind($provider, $externalStoreId, $tenantId);

            $connection = StoreConnection::query()->updateOrCreate(['provider' => $provider], [
                ...$attributes,
                'external_store_id' => $externalStoreId,
                'status' => StoreStatus::Active,
            ]);

            $this->audit->record(
                $isNew ? 'store.connected' : 'store.reauthorized',
                $actorId === null ? ActorType::System : ActorType::User,
                $actorId,
                subject: $connection,
            );

            return $connection;
        });

        StoreConnected::dispatch($connection->tenant_id, $connection->id);

        FetchStoreProfile::dispatch($connection->tenant_id)->afterCommit();

        if ($isNew) {
            BackfillStore::dispatch($connection->tenant_id)->afterCommit();
        }

        return $connection;
    }

    /**
     * Stop everything and drop the tokens and keys; data follows the
     * retention policy. The platform told us (Salla app.uninstalled).
     */
    public function uninstall(string $provider): void
    {
        $this->stop($provider, 'store.uninstalled', ActorType::System, null);
    }

    /**
     * The same stop, asked for from inside the app (WooCommerce disconnect).
     */
    public function disconnect(string $provider, ?int $actorId = null): void
    {
        $this->stop($provider, 'store.disconnected', $actorId === null ? ActorType::System : ActorType::User, $actorId);
    }

    private function stop(string $provider, string $action, ActorType $actorType, ?int $actorId): void
    {
        $connection = StoreConnection::query()->where('provider', $provider)->first();

        if ($connection === null) {
            return;
        }

        $connection->update([
            'status' => StoreStatus::Uninstalled,
            'access_token' => null,
            'refresh_token' => null,
            'credentials' => null,
        ]);

        $this->audit->record($action, $actorType, $actorId, subject: $connection);

        StoreDisconnected::dispatch($connection->tenant_id, $connection->id);
    }

    private static function platformName(string $provider): string
    {
        return match ($provider) {
            TenantRoute::SALLA => 'Salla',
            TenantRoute::WOOCOMMERCE => 'WooCommerce',
            default => ucfirst($provider),
        };
    }

    public function markNeedsReauth(StoreConnection $connection, string $reason): void
    {
        $connection->update(['status' => StoreStatus::NeedsReauth]);

        $this->audit->record('store.needs_reauth', ActorType::System, subject: $connection, context: ['reason' => $reason]);

        StoreDisconnected::dispatch($connection->tenant_id, $connection->id);
    }
}
