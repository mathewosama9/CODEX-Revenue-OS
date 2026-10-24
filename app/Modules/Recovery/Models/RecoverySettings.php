<?php

namespace App\Modules\Recovery\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property bool $enabled
 * @property int $first_delay_minutes
 * @property int $second_delay_minutes
 * @property int $max_messages
 * @property int $min_cart_halalas
 * @property int $frequency_days
 * @property int $max_cart_age_hours
 * @property bool $discount_enabled
 * @property int $discount_percent
 * @property string|null $discount_code
 * @property int $discount_cap_percent
 * @property int $margin_floor_bps
 * @property int $monthly_discount_budget_halalas
 * @property int $approval_window_minutes
 */
class RecoverySettings extends Model
{
    use BelongsToTenant;

    protected $table = 'recovery_settings';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'discount_enabled' => 'boolean'];
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([]);
    }
}
