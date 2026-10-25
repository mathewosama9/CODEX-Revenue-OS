<?php

namespace App\Modules\SalesAgent\Models;

use App\Modules\SalesAgent\AgentMode;
use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property AgentMode $mode
 * @property int $daily_cap_usd_micros
 * @property int|null $handoff_cart_halalas
 * @property CarbonImmutable|null $assisted_since
 * @property bool $autonomy_allowed_by_admin
 * @property int|null $autonomy_approved_by
 * @property CarbonImmutable|null $autonomy_approved_at
 * @property CarbonImmutable|null $auto_downgraded_at
 */
class AgentSettings extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mode' => AgentMode::class,
            'assisted_since' => 'immutable_datetime',
            'autonomy_allowed_by_admin' => 'boolean',
            'autonomy_approved_at' => 'immutable_datetime',
            'auto_downgraded_at' => 'immutable_datetime',
        ];
    }
}
