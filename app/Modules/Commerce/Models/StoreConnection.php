<?php

namespace App\Modules\Commerce\Models;

use App\Modules\Commerce\StoreStatus;
use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $provider
 * @property string $external_store_id
 * @property string|null $site_url
 * @property string|null $store_name
 * @property string|null $owner_email
 * @property string|null $access_token
 * @property string|null $refresh_token
 * @property array{consumer_key?: string, consumer_secret?: string, connector_secret?: string}|null $credentials WooCommerce only
 * @property CarbonImmutable|null $access_expires_at
 * @property string|null $scope
 * @property StoreStatus $status
 * @property CarbonImmutable|null $last_webhook_at
 * @property CarbonImmutable|null $last_reconciled_at
 * @property int $reconciliation_gap_count
 */
class StoreConnection extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /** Tokens never appear in logs, JSON, Sentry or the admin (09 §9.15.1). */
    protected $hidden = ['access_token', 'refresh_token', 'credentials'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'credentials' => 'encrypted:array',
            'access_expires_at' => 'immutable_datetime',
            'status' => StoreStatus::class,
            'last_webhook_at' => 'immutable_datetime',
            'last_reconciled_at' => 'immutable_datetime',
        ];
    }
}
