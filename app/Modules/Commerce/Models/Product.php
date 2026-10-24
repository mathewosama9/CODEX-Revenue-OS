<?php

namespace App\Modules\Commerce\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $external_id
 * @property string $name
 * @property string|null $sku
 * @property string|null $category_external_id
 * @property int|null $price_halalas
 * @property int|null $quantity
 * @property string|null $status
 */
class Product extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];
}
