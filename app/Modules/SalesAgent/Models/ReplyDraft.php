<?php

namespace App\Modules\SalesAgent\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An AI reply waiting for a member (Assisted, US-E11-01). Only an approved
 * draft is sent, and only inside the 24-hour window.
 *
 * @property int $id
 * @property int $agent_run_id
 * @property int $conversation_id
 * @property string $body the AI's words, encrypted at rest
 * @property string|null $final_body what was sent (differs when edited)
 * @property string $status pending|approved|edited|rejected|superseded|expired|failed
 * @property int|null $decided_by
 * @property CarbonImmutable|null $decided_at
 * @property string|null $send_error
 * @property CarbonImmutable $created_at
 */
class ReplyDraft extends Model
{
    use BelongsToTenant;

    public const PENDING = 'pending';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'body' => 'encrypted',
            'final_body' => 'encrypted',
            'decided_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<AgentRun, $this>
     */
    public function run(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class, 'agent_run_id');
    }
}
