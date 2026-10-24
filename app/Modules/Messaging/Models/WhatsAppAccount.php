<?php

namespace App\Modules\Messaging\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $waba_id
 * @property string $access_token
 * @property string $status
 */
class WhatsAppAccount extends Model
{
    use BelongsToTenant;

    protected $table = 'whatsapp_accounts';

    protected $guarded = ['id'];

    protected $hidden = ['access_token'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['access_token' => 'encrypted'];
    }

    /**
     * @return HasMany<WhatsAppNumber, $this>
     */
    public function numbers(): HasMany
    {
        return $this->hasMany(WhatsAppNumber::class);
    }
}
