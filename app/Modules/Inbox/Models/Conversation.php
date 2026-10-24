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
 */
class Conversation extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['last_message_at' => 'immutable_datetime'];
    }

    /**
     * @return HasMany<ConversationNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(ConversationNote::class);
    }
}
