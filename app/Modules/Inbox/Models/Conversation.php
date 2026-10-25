<?php

namespace App\Modules\Inbox\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $subject_hash
 * @property string $status open|pending|closed
 * @property string $reason
 * @property int|null $assigned_user_id
 * @property CarbonImmutable $last_message_at
 * @property string|null $phone E.164, encrypted at rest; needed to send an approved reply
 * @property CarbonImmutable|null $last_inbound_at
 * @property bool $ai_paused a member took over: the AI stays silent (US-E12-03)
 * @property int|null $ai_paused_by
 * @property CarbonImmutable|null $ai_paused_at
 */
class Conversation extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $hidden = ['phone'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'immutable_datetime',
            'last_inbound_at' => 'immutable_datetime',
            'ai_paused_at' => 'immutable_datetime',
            'ai_paused' => 'boolean',
            'phone' => 'encrypted',
        ];
    }

    /**
     * @return HasMany<ConversationNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ConversationNote::class);
    }
}
