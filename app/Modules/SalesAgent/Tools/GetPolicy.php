<?php

namespace App\Modules\SalesAgent\Tools;

use App\Modules\AiGateway\AgentTool;
use App\Modules\Knowledge\KnowledgeKind;
use App\Modules\Knowledge\KnowledgeRetriever;
use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * The merchant's own shipping, returns and FAQ text. Nothing written means
 * the run is handed off, whatever the model says (US-E09-02).
 */
final class GetPolicy implements AgentTool
{
    public function __construct(
        private readonly KnowledgeRetriever $knowledge,
        private readonly RunContext $context,
    ) {}

    public function name(): string
    {
        return 'get_policy';
    }

    public function description(): string
    {
        return 'The store\'s own shipping, returns or FAQ text. Answer only from what it returns.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'topic' => $schema->string()->enum(['shipping', 'returns', 'faq'])->required(),
            'question' => $schema->string()->description('The customer question, for the FAQ.'),
        ];
    }

    public function handle(array $arguments): array
    {
        $kind = KnowledgeKind::tryFrom((string) ($arguments['topic'] ?? '')) ?? KnowledgeKind::Faq;
        $passages = $this->knowledge->retrieve($kind, isset($arguments['question']) ? mb_substr((string) $arguments['question'], 0, 300) : null);

        if ($passages === []) {
            $this->context->policyMissing = true;

            return ['found' => false];
        }

        return ['found' => true, 'passages' => array_map(fn ($passage) => ['title' => $passage->title, 'text' => $passage->text], $passages)];
    }
}
