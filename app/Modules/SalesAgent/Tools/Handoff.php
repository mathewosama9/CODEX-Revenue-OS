<?php

namespace App\Modules\SalesAgent\Tools;

use App\Modules\AiGateway\AgentTool;
use App\Modules\SalesAgent\HandoffReason;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * Always available (Meta requires a path to a human). Records the wish;
 * the run then ends in a handoff, whatever text the model wrote.
 */
final class Handoff implements AgentTool
{
    public function __construct(private readonly RunContext $context) {}

    public function name(): string
    {
        return 'handoff';
    }

    public function description(): string
    {
        return 'Pass the conversation to a person: the customer asks for one, complains, asks for a discount or a better price, or you are not sure.';
    }

    public function schema(JsonSchema $schema): array
    {
        return ['reason' => $schema->string()->enum(['customer_request', 'complaint', 'discount_request', 'unsure'])->required()];
    }

    public function handle(array $arguments): array
    {
        $this->context->handoff = match ($arguments['reason'] ?? null) {
            'customer_request' => HandoffReason::CustomerAskedHuman,
            'complaint' => HandoffReason::Complaint,
            'discount_request' => HandoffReason::DiscountRequest,
            default => HandoffReason::AgentHandoff,
        };

        return ['handed_off' => true];
    }
}
