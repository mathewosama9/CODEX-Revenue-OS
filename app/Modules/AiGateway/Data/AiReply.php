<?php

namespace App\Modules\AiGateway\Data;

use App\Modules\AiGateway\AiStatus;
use App\Modules\AiGateway\ValidationResult;

final readonly class AiReply
{
    /**
     * @param  string  $text  with personal data restored, for the customer
     * @param  list<ToolOutcome>  $tools  every tool call of this turn, in order
     * @param  ValidationResult  $validation  run by the gateway on every reply, never optional
     */
    public function __construct(
        public AiStatus $status,
        public string $text,
        public array $tools,
        public ValidationResult $validation,
        public int $costUsdMicros,
        public ?int $callId,
    ) {}

    public static function unavailable(AiStatus $status, ?int $callId = null): self
    {
        return new self($status, '', [], new ValidationResult([]), 0, $callId);
    }

    /** Usable only if the provider answered and every number checked out. */
    public function ok(): bool
    {
        return $this->status === AiStatus::Ok && $this->validation->passed() && trim($this->text) !== '';
    }

    public function calledTool(string $name): bool
    {
        foreach ($this->tools as $tool) {
            if ($tool->name === $name) {
                return true;
            }
        }

        return false;
    }
}
