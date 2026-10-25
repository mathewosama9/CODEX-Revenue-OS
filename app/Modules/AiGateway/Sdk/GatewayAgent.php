<?php

namespace App\Modules\AiGateway\Sdk;

use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Attributes\CacheInstructions;
use Laravel\Ai\Attributes\CacheToolDefinitions;

/**
 * The single SDK agent class. Tests fake it with `GatewayAgent::fake([...])`
 * (see `App\Modules\AiGateway\Testing\FakeLlm`), so they run the SDK's real
 * tool loop without a key.
 */
#[CacheInstructions]
#[CacheToolDefinitions]
class GatewayAgent extends AnonymousAgent
{
    private int $steps = 6;

    private int $outputTokens = 600;

    private int $seconds = 20;

    public function limitedTo(int $steps, int $outputTokens, int $seconds): static
    {
        $this->steps = $steps;
        $this->outputTokens = $outputTokens;
        $this->seconds = $seconds;

        return $this;
    }

    public function maxSteps(): int
    {
        return $this->steps;
    }

    public function maxTokens(): int
    {
        return $this->outputTokens;
    }

    public function timeout(): int
    {
        return $this->seconds;
    }
}
