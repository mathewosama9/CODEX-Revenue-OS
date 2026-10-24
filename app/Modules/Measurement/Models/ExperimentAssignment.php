<?php

namespace App\Modules\Measurement\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only (database trigger).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $experiment_id
 * @property string $subject_hash
 * @property string $arm
 */
class ExperimentAssignment extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['assigned_at' => 'immutable_datetime'];
    }
}
