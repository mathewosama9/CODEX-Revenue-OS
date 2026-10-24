<?php

namespace App\Modules\Commerce\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $external_id
 * @property string $name
 */
class Category extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];
}
