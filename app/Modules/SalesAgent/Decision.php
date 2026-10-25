<?php

namespace App\Modules\SalesAgent;

/**
 * What the agent decided for one customer message, without side effects.
 */
final readonly class Decision
{
    /**
     * @param  list<string>  $tools  names of the tools it called
     */
    public function __construct(
        public ?string $text,
        public ?HandoffReason $handoff,
        public array $tools,
        public int $attempts,
        public int $validatorBlocks,
        public int $costUsdMicros,
    ) {}
}
