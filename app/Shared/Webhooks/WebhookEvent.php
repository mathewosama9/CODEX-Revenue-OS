<?php

namespace App\Shared\Webhooks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Platform-level inbox for inbound webhooks. Not tenant-scoped: the tenant is
 * resolved from a mapping table while processing, never from the payload.
 *
 * @property int $id
 * @property string $provider
 * @property string $external_event_id
 * @property string $event_type
 * @property int|null $tenant_id
 * @property array<string, mixed> $payload
 * @property WebhookStatus $status
 * @property string|null $error
 * @property int $attempts
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 */
class WebhookEvent extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'status' => WebhookStatus::class,
            'received_at' => 'immutable_datetime',
            'processed_at' => 'immutable_datetime',
        ];
    }
}
