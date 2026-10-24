<?php

namespace App\Modules\Measurement\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * Append-only (database trigger).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $kind
 * @property int $amount_micros
 * @property string $reference
 * @property string|null $subject_hash
 * @property array<string, mixed> $meta
 * @property CarbonImmutable $occurred_at
 */
class CostLedgerEntry extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['meta' => 'array', 'occurred_at' => 'immutable_datetime'];
    }
}
