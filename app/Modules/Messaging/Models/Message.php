<?php

namespace App\Modules\Messaging\Models;

use App\Modules\Messaging\MessageStatus;
use App\Shared\Tenancy\BelongsToTenant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int|null $whatsapp_number_id
 * @property string $direction
 * @property string|null $wamid
 * @property string|null $idempotency_key
 * @property string|null $subject_hash
 * @property string|null $template_name
 * @property string|null $category
 * @property MessageStatus $status
 * @property string|null $error_code
 * @property string|null $error_title
 * @property string|null $body encrypted
 * @property array<string, mixed> $context
 * @property string|null $pricing_category
 * @property bool|null $billable
 * @property CarbonImmutable|null $sent_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $read_at
 * @property CarbonImmutable|null $failed_at
 * @property CarbonImmutable $created_at
 */
class Message extends Model
{
    use BelongsToTenant;

    public const OUTBOUND = 'outbound';

    public const INBOUND = 'inbound';

    protected $guarded = ['id'];

    protected $hidden = ['body'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MessageStatus::class,
            'body' => 'encrypted',
            'context' => 'array',
            'billable' => 'boolean',
            'sent_at' => 'immutable_datetime',
            'delivered_at' => 'immutable_datetime',
            'read_at' => 'immutable_datetime',
            'failed_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }
}
