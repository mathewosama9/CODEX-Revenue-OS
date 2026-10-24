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
}
