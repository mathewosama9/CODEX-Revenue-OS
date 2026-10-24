<?php

namespace App\Modules\Commerce\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $external_id
 * @property int|null $customer_id
 * @property string|null $phone_hash
 * @property int $total_halalas
 * @property int $discount_halalas
 * @property int $shipping_halalas
 * @property int $refunded_halalas
 * @property string $currency
 * @property string|null $status
 * @property string|null $payment_method
 * @property string|null $coupon_code
 * @property CarbonImmutable $placed_at
 * @property CarbonImmutable|null $cancelled_at
 */
class Order extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'placed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
