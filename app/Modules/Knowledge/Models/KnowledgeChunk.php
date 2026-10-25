<?php

namespace App\Modules\Knowledge\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $document_id
 * @property int $position
 * @property string $content as written
 * @property string $search_text normalized
 * @property CarbonImmutable|null $embedded_at
 */
class KnowledgeChunk extends Model
{
    use BelongsToTenant;

    protected $guarded = ['id'];

    protected $hidden = ['embedding', 'tsv'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['embedded_at' => 'immutable_datetime'];
    }

    /**
     * @return BelongsTo<KnowledgeDocument, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(KnowledgeDocument::class, 'document_id');
    }
}
