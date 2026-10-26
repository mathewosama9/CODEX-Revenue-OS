<?php

namespace App\Shared\Integrations;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $provider
 * @property string $external_id
 * @property int $tenant_id
 */
class TenantRoute extends Model
{
    public const SALLA = 'salla';

    public const META = 'meta';

    /** WordPress + WooCommerce through the CODEX Connector plugin (trials; docs/integrations/woocommerce-connector.md). */
    public const WOOCOMMERCE = 'woocommerce';

    /** Every commerce platform a store can come from; the scheduled commerce jobs loop over these. */
    public const COMMERCE = [self::SALLA, self::WOOCOMMERCE];

    protected $guarded = ['id'];

    public static function tenantFor(string $provider, string|int $externalId): ?int
    {
        return static::query()
            ->where('provider', $provider)
            ->where('external_id', (string) $externalId)
            ->value('tenant_id');
    }

    public static function bind(string $provider, string|int $externalId, int $tenantId): void
    {
        static::query()->updateOrCreate(
            ['provider' => $provider, 'external_id' => (string) $externalId],
            ['tenant_id' => $tenantId],
        );
    }

    /**
     * @return list<int>
     */
    public static function tenantsFor(string $provider): array
    {
        /** @var list<int> */
        return static::query()->where('provider', $provider)->distinct()->pluck('tenant_id')->all();
    }

    /**
     * Tenants with a store on any commerce platform.
     *
     * @return list<int>
     */
    public static function commerceTenants(): array
    {
        /** @var list<int> */
        return static::query()->whereIn('provider', self::COMMERCE)->distinct()->pluck('tenant_id')->all();
    }
}
