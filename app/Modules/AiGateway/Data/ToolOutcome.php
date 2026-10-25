<?php

namespace App\Modules\AiGateway\Data;

/**
 * One tool call the model made and what our code answered.
 */
final readonly class ToolOutcome
{
    /**
     * @param  array<string, mixed>  $arguments
     * @param  array<string, mixed>  $result
     */
    public function __construct(
        public string $name,
        public array $arguments,
        public array $result,
        public bool $failed = false,
    ) {}
}
