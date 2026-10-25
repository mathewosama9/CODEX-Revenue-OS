<?php

namespace App\Modules\AiGateway\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Metadata and cost of one LLM or embeddings call. Never the prompt or the
 * reply (ADR-008).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $purpose
 * @property string|null $conversation_ref
 * @property string $provider
 * @property string $model
 * @property string $status
 * @property int $input_tokens
 * @property int $output_tokens
 * @property int $cache_read_tokens
 * @property int $cache_write_tokens
 * @property int $cost_usd_micros
 * @property int $latency_ms
 * @property int $tool_calls
 * @property int $pseudonymized
 * @property string|null $error
 * @property CarbonImmutable $created_at
 */
class AiCall extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
