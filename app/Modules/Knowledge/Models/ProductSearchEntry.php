<?php

namespace App\Modules\Knowledge\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * One listed product in the search index. Text only: prices and stock are
 * read live by `get_product`, never from here (09 §9.12).
 *
 * @property int $id
 * @property string $product_external_id
 * @property string $search_text normalized
 * @property string $content_hash
 * @property CarbonImmutable|null $embedded_at
 */
class ProductSearchEntry extends Model
{
    use BelongsToTenant;

    protected $table = 'product_search_index';

    protected $guarded = ['id'];

    /** The vector and the generated tsvector stay in the database. */
    protected $hidden = ['embedding', 'tsv'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['embedded_at' => 'immutable_datetime'];
    }
}
