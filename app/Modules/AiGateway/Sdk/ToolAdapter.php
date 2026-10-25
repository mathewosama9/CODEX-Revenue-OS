<?php

namespace App\Modules\AiGateway\Sdk;

use App\Modules\AiGateway\AgentTool;
use App\Modules\AiGateway\Data\ToolOutcome;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Throwable;

/**
 * Runs one of our tools for the SDK and records what it returned, so the
 * validator can check the reply against it. A failing tool tells the model
 * only "unavailable" (no internal error text), and its numbers never count.
 */
final class ToolAdapter implements Tool
{
    public function __construct(
        private readonly AgentTool $tool,
        private readonly ToolRecorder $recorder,
    ) {}

    public function name(): string
    {
        return $this->tool->name();
    }

    public function description(): string
    {
        return $this->tool->description();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->tool->schema($schema);
    }

    public function handle(Request $request): string
    {
        $arguments = $request->all();

        try {
            $result = $this->tool->handle($arguments);
            $this->recorder->add(new ToolOutcome($this->tool->name(), $arguments, $result));
        } catch (Throwable $exception) {
            Log::warning('Agent tool failed.', ['tool' => $this->tool->name(), 'error' => $exception::class]);
            $result = ['error' => 'unavailable'];
            $this->recorder->add(new ToolOutcome($this->tool->name(), $arguments, $result, failed: true));
        }

        return (string) json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
