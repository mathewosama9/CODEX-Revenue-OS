<?php

namespace App\Modules\Recovery\Models;

use App\Modules\Recovery\SequenceStatus;
use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $cart_id
 * @property string|null $subject_hash
 * @property string|null $arm
 * @property SequenceStatus $status
 * @property string|null $reason
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $ended_at
 */
class RecoverySequence extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SequenceStatus::class,
            'started_at' => 'immutable_datetime',
            'ended_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<ScheduledAction, $this>
     */
    public function actions(): HasMany
    {
        return $this->hasMany(ScheduledAction::class);
    }
}
