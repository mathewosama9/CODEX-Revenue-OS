<?php

namespace App\Modules\AiGateway\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * One golden-set run (US-E10-05). CODEX-level, not tenant data: the cases
 * are written by us, never taken from a merchant's chats.
 *
 * @property int $id
 * @property string $suite
 * @property string $provider
 * @property string $model
 * @property string $prompt_version
 * @property int $cases
 * @property int $passed
 * @property int $price_cases
 * @property int $price_passed
 * @property bool $blocking_failure
 * @property int $cost_usd_micros
 * @property list<array<string, mixed>> $failures
 * @property CarbonImmutable $created_at
 */
class EvalRun extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['failures' => 'array', 'blocking_failure' => 'boolean', 'created_at' => 'immutable_datetime'];
    }
}
