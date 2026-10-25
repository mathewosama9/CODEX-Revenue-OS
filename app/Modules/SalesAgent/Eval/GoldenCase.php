<?php

namespace App\Modules\SalesAgent\Eval;

use App\Modules\AiGateway\Data\ChatTurn;

/**
 * One golden-set case (format: docs/phases/P3-ai-agent/golden-set-guide.md).
 */
final readonly class GoldenCase
{
    /**
     * @param  list<ChatTurn>  $turns
     * @param  list<string>  $cartItems  product ids in the customer's saved cart
     * @param  list<string>  $tools
     * @param  list<string>  $contains
     * @param  list<string>  $notContains
     * @param  list<array<string, mixed>>|null  $offline  scripted model for CI, or null
     * @param  self|null  $live  other expectations for the real model, when the scripted bad reply is not what it does
     */
    public function __construct(
        public string $id,
        public string $category,
        public string $dialect,
        public array $turns,
        public ?int $cartSar,
        public array $cartItems,
        public string $outcome,
        public ?string $reason,
        public array $tools,
        public array $contains,
        public array $notContains,
        public bool $price,
        public ?array $offline,
        public ?self $live = null,
    ) {}

    /** Handoffs decided by code before any model call: testable without a model. */
    public function deterministic(): bool
    {
        return $this->outcome === 'handoff'
            && in_array($this->reason, ['asked_human', 'complaint', 'discount_request', 'high_cart_value'], true);
    }
}
