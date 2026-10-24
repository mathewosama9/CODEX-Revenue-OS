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
 * @property string $subject_hash
 * @property string $reference
 * @property CarbonImmutable $delivered_at
 */
class Touch extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['delivered_at' => 'immutable_datetime'];
    }
}
