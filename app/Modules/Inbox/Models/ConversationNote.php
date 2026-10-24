<?php

namespace App\Modules\Inbox\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int|null $user_id
 * @property string $body encrypted
 * @property CarbonImmutable $created_at
 */
class ConversationNote extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['body' => 'encrypted', 'created_at' => 'immutable_datetime'];
    }
}
