<?php

namespace App\Modules\Commerce\Models;

use App\Modules\Commerce\CartStatus;
use App\Modules\Commerce\DiscoveredBy;
use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $external_id
 * @property int|null $customer_id
 * @property string|null $phone_hash
 * @property int $total_halalas
 * @property string $currency
 * @property int $item_count
 * @property string|null $checkout_url
 * @property CartStatus $status
 * @property CarbonImmutable|null $abandoned_at
 * @property CarbonImmutable|null $purchased_at
 * @property DiscoveredBy $discovered_by
 * @property-read Customer|null $customer
 */
class Cart extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CartStatus::class,
            'discovered_by' => DiscoveredBy::class,
            'abandoned_at' => 'immutable_datetime',
            'purchased_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }
}
