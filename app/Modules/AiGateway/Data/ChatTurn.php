<?php

namespace App\Modules\AiGateway\Data;

final readonly class ChatTurn
{
    public const CUSTOMER = 'customer';

    public const ASSISTANT = 'assistant';

    public function __construct(
        public string $role,
        public string $text,
    ) {}

    public static function customer(string $text): self
    {
        return new self(self::CUSTOMER, $text);
    }

    public static function assistant(string $text): self
    {
        return new self(self::ASSISTANT, $text);
    }
}
