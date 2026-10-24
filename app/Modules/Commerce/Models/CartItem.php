<?php

namespace App\Modules\Commerce\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cart_id
 * @property string|null $product_external_id
 * @property string|null $name
 * @property int $quantity
 * @property int $unit_price_halalas
 */
class CartItem extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $guarded = ['id'];
}
