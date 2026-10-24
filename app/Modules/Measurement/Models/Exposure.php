<?php

namespace App\Modules\Measurement\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only (database trigger).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $experiment_id
 * @property string $subject_hash
 * @property string $arm
 * @property int $cart_id
 * @property int $cart_total_halalas
 * @property CarbonImmutable $eligible_at
 */
class Exposure extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['eligible_at' => 'immutable_datetime'];
    }
}
