<?php

namespace App\Modules\Measurement\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string|null $category_external_id
 * @property int|null $margin_bps null = cleared, the store default applies
 * @property CarbonImmutable $effective_from
 * @property int|null $created_by
 */
class CategoryMargin extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['effective_from' => 'immutable_datetime'];
    }
}
