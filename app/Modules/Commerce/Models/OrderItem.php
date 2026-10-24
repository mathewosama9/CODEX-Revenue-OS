<?php

namespace App\Modules\Commerce\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $order_id
 * @property string|null $product_external_id
 * @property string|null $category_external_id
 * @property int $quantity
 * @property int $unit_price_halalas
 */
class OrderItem extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $guarded = ['id'];
}
