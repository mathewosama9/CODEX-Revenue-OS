<?php

namespace App\Modules\Commerce\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $external_id
 * @property string $name
 * @property string|null $sku
 * @property string|null $category_external_id
 * @property int|null $price_halalas
 * @property int|null $quantity
 * @property string|null $status sale|out|hidden|deleted (Salla)
 * @property string|null $description plain text
 * @property string|null $url
 * @property int|null $regular_price_halalas
 * @property bool|null $is_available
 * @property bool $unlimited_quantity
 * @property list<array{external_id: string, name: string, price_halalas: int|null, quantity: int|null, unlimited_quantity: bool}>|null $variants
 * @property CarbonImmutable|null $synced_at
 */
class Product extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'is_available' => 'boolean',
            'unlimited_quantity' => 'boolean',
            'synced_at' => 'immutable_datetime',
        ];
    }
}
