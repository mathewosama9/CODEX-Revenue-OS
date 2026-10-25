<?php

namespace App\Modules\Messaging;

use RuntimeException;
use Throwable;

/**
 * Embedded Signup could not finish, for a reason the merchant can act on
 * (review B9). The reason is a stable code the app translates.
 */
class WhatsAppSignupFailed extends RuntimeException
{
    public const REGION_NOT_SET = 'region_not_set';

    public const ALREADY_CONNECTED = 'already_connected';

    /** The one-time code from Meta was rejected (expired or already used). */
    public const CODE_REJECTED = 'code_rejected';

    /** Meta refused to register the number, most often a wrong two-step PIN. */
    public const REGISTRATION_FAILED = 'registration_failed';

    public const META_ERROR = 'meta_error';

    public function __construct(public readonly string $reason, string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, previous: $previous);
    }
}
