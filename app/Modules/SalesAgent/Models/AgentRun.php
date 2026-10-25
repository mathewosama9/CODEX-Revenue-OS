<?php

namespace App\Modules\SalesAgent\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * What the agent did for one customer message: the read-only log staff see
 * in the inbox (E12b). The trace holds tool names and product ids, never
 * message text (ADR-008).
 *
 * @property int $id
 * @property int $conversation_id
 * @property int $inbound_message_id
 * @property string $mode
 * @property string $status drafted|sent|handed_off|skipped|superseded
 * @property string|null $handoff_reason
 * @property int $attempts
 * @property int $validator_blocks
 * @property list<array<string, mixed>> $trace
 * @property int $cost_usd_micros
 * @property CarbonImmutable $created_at
 */
class AgentRun extends Model
{
    use BelongsToTenant;

    public const DRAFTED = 'drafted';

    public const SENT = 'sent';

    public const HANDED_OFF = 'handed_off';

    public const SKIPPED = 'skipped';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['trace' => 'array', 'created_at' => 'immutable_datetime'];
    }

    /**
     * @return HasOne<ReplyDraft, $this>
     */
    public function draft(): HasOne
    {
        return $this->hasOne(ReplyDraft::class);
    }
}
