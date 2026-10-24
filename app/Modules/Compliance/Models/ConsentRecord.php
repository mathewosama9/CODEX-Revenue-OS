<?php

namespace App\Modules\Compliance\Models;

use App\Modules\Compliance\Consent\ConsentChannel;
use App\Modules\Compliance\Consent\ConsentPurpose;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Modules\Compliance\Consent\ConsentStatus;
use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $subject_hash
 * @property ConsentChannel $channel
 * @property ConsentPurpose $purpose
 * @property ConsentStatus $status
 * @property ConsentSource $source
 * @property array<string, mixed> $evidence
 * @property CarbonImmutable $recorded_at
 */
class ConsentRecord extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('consent_records is append-only.'));
        static::deleting(fn () => throw new LogicException('consent_records is append-only.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => ConsentChannel::class,
            'purpose' => ConsentPurpose::class,
            'status' => ConsentStatus::class,
            'source' => ConsentSource::class,
            'evidence' => 'array',
            'recorded_at' => 'immutable_datetime',
        ];
    }
}
