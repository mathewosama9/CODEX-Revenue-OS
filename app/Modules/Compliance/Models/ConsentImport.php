<?php

namespace App\Modules\Compliance\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $reference
 * @property int|null $user_id
 * @property string $file_sha256
 * @property string $consent_text
 * @property string $consent_text_sha256
 * @property string $collected_via
 * @property string $attestation_version
 * @property int $rows_total
 * @property int $granted
 * @property int $already_granted
 * @property int $skipped_opted_out
 * @property int $skipped_invalid
 * @property CarbonImmutable $created_at
 */
class ConsentImport extends Model
{
    use BelongsToTenant;

    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('consent_imports is append-only.'));
        static::deleting(fn () => throw new LogicException('consent_imports is append-only.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }
}
