<?php

namespace App\Modules\Recovery\Models;

use App\Modules\Recovery\ActionStatus;
use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $recovery_sequence_id
 * @property int $step
 * @property ActionStatus $status
 * @property CarbonImmutable $run_at
 * @property string $idempotency_key
 * @property int $attempts
 * @property int $reclaims lost runs picked up again (H1)
 * @property string|null $last_error
 * @property CarbonImmutable|null $claimed_at
 * @property array<string, mixed> $result
 * @property-read RecoverySequence $sequence
 */
class ScheduledAction extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ActionStatus::class,
            'run_at' => 'immutable_datetime',
            'claimed_at' => 'immutable_datetime',
            'result' => 'array',
        ];
    }

    /**
     * @return BelongsTo<RecoverySequence, $this>
     */
    public function sequence(): BelongsTo
    {
        return $this->belongsTo(RecoverySequence::class, 'recovery_sequence_id');
    }
}
