<?php

namespace App\Modules\Knowledge\Models;

use App\Modules\Knowledge\KnowledgeKind;
use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * FAQ, shipping or returns text the merchant wrote (US-E09-02).
 *
 * @property int $id
 * @property KnowledgeKind $kind
 * @property string $title
 * @property string $body
 * @property int|null $updated_by
 * @property CarbonImmutable $updated_at
 */
class KnowledgeDocument extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['kind' => KnowledgeKind::class, 'updated_at' => 'immutable_datetime'];
    }

    /**
     * @return HasMany<KnowledgeChunk, $this>
     */
    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'document_id');
    }
}
