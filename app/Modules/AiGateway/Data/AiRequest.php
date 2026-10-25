<?php

namespace App\Modules\AiGateway\Data;

use App\Modules\AiGateway\AgentTool;

final readonly class AiRequest
{
    /**
     * @param  list<ChatTurn>  $history  oldest first, the last turn is the customer's
     * @param  list<AgentTool>  $tools
     * @param  list<string>  $knownNames  personal names to hide from the model
     */
    public function __construct(
        public string $purpose,
        public string $instructions,
        public array $history,
        public array $tools,
        public int $dailyCapUsdMicros,
        public ?string $conversationRef = null,
        public ?string $subjectHash = null,
        public array $knownNames = [],
    ) {}
}
