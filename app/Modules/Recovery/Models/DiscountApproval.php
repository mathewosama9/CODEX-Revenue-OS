<?php

namespace App\Modules\Recovery\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $scheduled_action_id
 * @property int $percent
 * @property string $reason
 * @property string $status pending|approved|rejected|expired
 * @property CarbonImmutable $expires_at
 * @property int|null $decided_by
 * @property CarbonImmutable|null $decided_at
 * @property-read ScheduledAction $action
 */
class DiscountApproval extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['expires_at' => 'immutable_datetime', 'decided_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<ScheduledAction, $this>
     */
    public function action(): BelongsTo
    {
        return $this->belongsTo(ScheduledAction::class, 'scheduled_action_id');
    }
}
