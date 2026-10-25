<?php

namespace App\Modules\SalesAgent\Http;

use App\Modules\Commerce\Commerce;
use App\Modules\Inbox\Inbox;
use App\Modules\SalesAgent\Drafts;
use App\Modules\SalesAgent\HandoffReason;
use App\Modules\SalesAgent\Models\AgentRun;
use App\Modules\SalesAgent\Models\ReplyDraft;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Drafts screen (E12b) and the read-only AI log of a conversation.
 */
class DraftsController
{
    public function __construct(
        private readonly Drafts $drafts,
        private readonly Inbox $inbox,
        private readonly Commerce $commerce,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate(['status' => ['nullable', Rule::in(['pending', 'decided'])]]);
        $pending = ($validated['status'] ?? 'pending') === 'pending';

        $drafts = ReplyDraft::query()
            ->when($pending, fn ($query) => $query->where('status', ReplyDraft::PENDING), fn ($query) => $query->where('status', '!=', ReplyDraft::PENDING))
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json(['data' => $drafts->map(fn (ReplyDraft $draft) => $this->draft($draft, withPhone: true))->values()]);
    }

    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate(['body' => ['nullable', 'string', 'max:1500']]);
        $draft = $this->drafts->approve($this->find($request), (int) $request->user()?->id, $validated['body'] ?? null);

        return response()->json(['data' => $this->draft($draft)]);
    }

    public function reject(Request $request): JsonResponse
    {
        $draft = $this->drafts->reject($this->find($request), (int) $request->user()?->id);

        return response()->json(['data' => $this->draft($draft)]);
    }

    /** What the agent did in one conversation: runs, tools, handoffs, drafts. */
    public function activity(Request $request): JsonResponse
    {
        $conversationId = (int) $request->route('conversation');
        $conversation = $this->inbox->conversation($conversationId) ?? abort(404);

        $runs = AgentRun::query()->with('draft')->where('conversation_id', $conversationId)->latest('id')->limit(20)->get();

        return response()->json(['data' => [
            'ai_paused' => $conversation->aiPaused,
            'runs' => $runs->map(fn (AgentRun $run) => [
                'id' => $run->id,
                'status' => $run->status,
                'handoff_reason' => $run->handoff_reason,
                'handoff_kind' => HandoffReason::tryFrom((string) $run->handoff_reason) === null ? null : 'ai',
                'attempts' => $run->attempts,
                'validator_blocks' => $run->validator_blocks,
                'tools' => $run->trace,
                'draft' => $run->draft === null ? null : $this->draft($run->draft),
                'at' => $run->created_at->toIso8601String(),
            ])->values(),
        ]]);
    }

    private function find(Request $request): ReplyDraft
    {
        return ReplyDraft::query()->findOrFail((int) $request->route('draft'));
    }

    /**
     * @return array<string, mixed>
     */
    private function draft(ReplyDraft $draft, bool $withPhone = false): array
    {
        $data = [
            'id' => $draft->id,
            'conversation_id' => $draft->conversation_id,
            'status' => $draft->status,
            'body' => $draft->body,
            'final_body' => $draft->final_body,
            'send_error' => $draft->send_error,
            'decided_by' => $draft->decided_by,
            'decided_at' => $draft->decided_at?->toIso8601String(),
            'at' => $draft->created_at->toIso8601String(),
        ];

        if ($withPhone) {
            $hash = $this->inbox->conversation($draft->conversation_id)?->subjectHash;
            $data['phone'] = $hash === null ? null : $this->commerce->maskedPhone($hash);
        }

        return $data;
    }
}
