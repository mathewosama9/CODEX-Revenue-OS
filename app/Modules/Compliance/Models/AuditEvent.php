<?php

namespace App\Modules\Compliance\Models;

use App\Modules\Compliance\ActorType;
use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * @property int $id
 * @property int $tenant_id
 * @property ActorType $actor_type
 * @property int|null $actor_id
 * @property string $action
 * @property string|null $subject_type
 * @property string|null $subject_id
 * @property array<string, mixed> $context
 * @property string|null $correlation_id
 * @property Carbon $occurred_at
 */
class AuditEvent extends Model
{
    use BelongsToTenant;

    public $timestamps = false;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        // The database trigger is the real guard; this fails earlier with a clearer message.
        static::updating(fn () => throw new LogicException('audit_events is append-only.'));
        static::deleting(fn () => throw new LogicException('audit_events is append-only.'));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actor_type' => ActorType::class,
            'context' => 'array',
            'occurred_at' => 'immutable_datetime',
        ];
    }
}
