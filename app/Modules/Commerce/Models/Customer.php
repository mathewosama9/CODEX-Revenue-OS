<?php

namespace App\Modules\Commerce\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $external_id
 * @property string|null $phone_hash
 * @property string|null $phone E.164, encrypted at rest
 */
class Customer extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $hidden = ['phone'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['phone' => 'encrypted'];
    }
}
