<?php

namespace App\Modules\Compliance;

use App\Modules\Compliance\Models\AuditEvent;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * The only way other modules write to the audit log (US-E15-01).
 *
 * `context` holds IDs, policy decisions and reasons — never names, phones,
 * emails or message text. Keys that look like PII are rejected.
 */
class AuditLogger
{
    private const PII_KEYS = [
        'phone', 'mobile', 'msisdn', 'email', 'name', 'first_name', 'last_name',
        'address', 'message', 'body', 'text', 'content',
    ];

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        string $action,
        ActorType $actorType,
        ?int $actorId = null,
        ?Model $subject = null,
        array $context = [],
        ?string $correlationId = null,
    ): void {
        $this->guardAgainstPii($context);

        AuditEvent::create([
            'actor_type' => $actorType,
            'actor_id' => $actorId,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject === null ? null : (string) $subject->getKey(),
            'context' => $context,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * @param  array<array-key, mixed>  $context
     */
    private function guardAgainstPii(array $context): void
    {
        foreach ($context as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::PII_KEYS, true)) {
                throw new InvalidArgumentException("Audit context key [{$key}] looks like personal data.");
            }

            if (is_array($value)) {
                $this->guardAgainstPii($value);
            }
        }
    }
}
