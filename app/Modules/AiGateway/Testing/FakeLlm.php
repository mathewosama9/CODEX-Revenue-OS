<?php

namespace App\Modules\AiGateway\Testing;

use App\Modules\AiGateway\Sdk\GatewayAgent;
use Closure;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Gateway\FakeTextGateway;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\TextResponse;

/**
 * Scripted model for tests and the offline eval: tool calls run through the
 * SDK's real loop and our real tools, with no key and no cost beyond what
 * the script declares.
 *
 *     FakeLlm::script([
 *         FakeLlm::tool('get_product', ['product_id' => 'p1']),
 *         FakeLlm::reply('السعر 199 ريال'),
 *     ]);
 */
final class FakeLlm
{
    /**
     * @param  list<ToolCall|TextResponse|Closure>  $steps
     */
    public static function script(array $steps): FakeTextGateway
    {
        return GatewayAgent::fake($steps)->preventStrayPrompts();
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public static function tool(string $name, array $arguments = []): ToolCall
    {
        return new ToolCall('call_'.bin2hex(random_bytes(4)), $name, $arguments);
    }

    public static function reply(string $text, int $inputTokens = 2000, int $outputTokens = 120): TextResponse
    {
        return new TextResponse($text, new TextUsage($inputTokens, $outputTokens), new Meta('anthropic', 'claude-haiku-5-5'));
    }

    public static function down(): Closure
    {
        return fn () => throw new ProviderConnectionException('fake provider down');
    }
}
