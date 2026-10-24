<?php

namespace App\Modules\Measurement\Models;

use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property CarbonImmutable $period_start
 * @property CarbonImmutable $period_end
 * @property string $method_version
 * @property array<string, mixed> $payload
 * @property CarbonImmutable $computed_at
 */
class ProfitReportSnapshot extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'immutable_date',
            'period_end' => 'immutable_date',
            'payload' => 'array',
            'computed_at' => 'immutable_datetime',
        ];
    }
}
