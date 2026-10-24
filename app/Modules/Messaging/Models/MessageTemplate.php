<?php

namespace App\Modules\Messaging\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $whatsapp_account_id
 * @property string|null $external_id
 * @property string $name
 * @property string $language
 * @property string $category
 * @property string $status
 * @property string|null $purpose
 * @property list<array<string, mixed>> $components
 */
class MessageTemplate extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['components' => 'array'];
    }
}
