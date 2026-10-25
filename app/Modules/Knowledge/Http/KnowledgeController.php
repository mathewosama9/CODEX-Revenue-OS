<?php

namespace App\Modules\Knowledge\Http;

use App\Modules\Knowledge\KnowledgeBase;
use App\Modules\Knowledge\KnowledgeKind;
use App\Modules\Knowledge\Models\KnowledgeDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The merchant's knowledge editor (US-E09-02): FAQ, shipping, returns.
 */
class KnowledgeController
{
    public function __construct(private readonly KnowledgeBase $knowledge) {}

    public function index(): JsonResponse
    {
        return response()->json(['data' => KnowledgeDocument::query()->orderBy('kind')->orderBy('id')->get()
            ->map(fn (KnowledgeDocument $document) => self::document($document))->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $document = $this->knowledge->save(null, KnowledgeKind::from($validated['kind']), $validated['title'], $validated['body'], (int) $request->user()?->id);

        return response()->json(['data' => self::document($document)], 201);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $this->validated($request);
        $document = $this->knowledge->save($this->find($request), KnowledgeKind::from($validated['kind']), $validated['title'], $validated['body'], (int) $request->user()?->id);

        return response()->json(['data' => self::document($document)]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->knowledge->delete($this->find($request));

        return response()->json(null, 204);
    }

    /**
     * @return array{kind: string, title: string, body: string}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'kind' => ['required', Rule::enum(KnowledgeKind::class)],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:20000'],
        ]);

        return ['kind' => (string) $validated['kind'], 'title' => (string) $validated['title'], 'body' => (string) $validated['body']];
    }

    private function find(Request $request): KnowledgeDocument
    {
        return KnowledgeDocument::query()->findOrFail((int) $request->route('document'));
    }

    /**
     * @return array<string, mixed>
     */
    private static function document(KnowledgeDocument $document): array
    {
        return [
            'id' => $document->id,
            'kind' => $document->kind->value,
            'title' => $document->title,
            'body' => $document->body,
            'updated_at' => $document->updated_at->toIso8601String(),
        ];
    }
}
