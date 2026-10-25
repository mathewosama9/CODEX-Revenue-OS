<?php

namespace App\Modules\AiGateway;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;

/**
 * A tool the model may call. Modules implement this, never the SDK's own
 * contract, so the SDK stays behind the gateway (US-E10-01).
 *
 * The tenant comes from the server context, never from the arguments: the
 * model cannot ask for another store's data, even under prompt injection
 * (09 §9.11.2).
 */
interface AgentTool
{
    public function name(): string;

    public function description(): string;

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array;

    /**
     * Facts only. Every number in the reply must come from what a tool
     * returned (US-E10-03), so return the values the customer may see.
     *
     * @param  array<string, mixed>  $arguments
     * @return array<string, mixed>
     */
    public function handle(array $arguments): array;
}
