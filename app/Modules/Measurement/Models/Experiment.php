<?php

namespace App\Modules\Measurement\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $key
 * @property int $holdout_bps
 * @property string $salt
 * @property bool $holdout_locked
 * @property CarbonImmutable|null $srm_flagged_at
 * @property CarbonImmutable $created_at
 */
class Experiment extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $hidden = ['salt'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'holdout_locked' => 'boolean',
            'srm_flagged_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
