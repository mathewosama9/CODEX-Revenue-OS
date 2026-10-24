<?php

namespace App\Modules\Messaging\Models;

use App\Modules\Messaging\QualityRating;
use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $whatsapp_account_id
 * @property string $phone_number_id
 * @property string|null $display_phone_number
 * @property QualityRating $quality_rating
 * @property string $status
 * @property-read WhatsAppAccount $account
 */
class WhatsAppNumber extends Model
{
    use BelongsToTenant;

    protected $table = 'whatsapp_numbers';

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['quality_rating' => QualityRating::class];
    }

    /**
     * @return BelongsTo<WhatsAppAccount, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(WhatsAppAccount::class, 'whatsapp_account_id');
    }
}
