<?php

namespace App\Modules\Messaging\Data;

final readonly class SendResult
{
    public function __construct(
        public SendOutcome $outcome,
        public ?int $messageId = null,
        public ?string $errorCode = null,
    ) {}

    public function accepted(): bool
    {
        return $this->outcome === SendOutcome::Accepted;
    }
}
