<?php

namespace App\Modules\Recovery;

final readonly class DiscountDecision
{
    private function __construct(
        public string $kind,
        public int $percent = 0,
        public ?string $code = null,
        public ?string $reason = null,
    ) {}

    public static function none(string $reason): self
    {
        return new self('none', reason: $reason);
    }

    public static function apply(int $percent, string $code): self
    {
        return new self('apply', $percent, $code);
    }

    public static function needsApproval(int $percent, string $reason): self
    {
        return new self('needs_approval', $percent, reason: $reason);
    }

    public function applies(): bool
    {
        return $this->kind === 'apply';
    }

    public function requiresApproval(): bool
    {
        return $this->kind === 'needs_approval';
    }
}
