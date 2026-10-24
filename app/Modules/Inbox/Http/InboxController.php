<?php

namespace App\Modules\Inbox\Http;

use App\Models\Team;
use App\Modules\Commerce\Commerce;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Inbox\Models\Conversation;
use App\Modules\Inbox\Models\ConversationNote;
use App\Modules\Messaging\Messaging;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InboxController
{
    public function __construct(
        private readonly Commerce $commerce,
        private readonly Messaging $messaging,
        private readonly AuditLogger $audit,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', 'open');

        return response()->json(['data' => Conversation::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest('last_message_at')
            ->limit(100)
            ->get()
            ->map(fn (Conversation $conversation) => $this->summary($conversation))
            ->values()]);
    }

    public function show(Request $request): JsonResponse
    {
        $conversation = $this->conversation($request);

        return response()->json(['data' => $this->summary($conversation) + [
            'messages' => $this->messaging->conversation($conversation->subject_hash),
            'notes' => $conversation->notes()->latest('id')->get()->map(fn (ConversationNote $note) => [
                'body' => $note->body,
                'user_id' => $note->user_id,
                'at' => $note->created_at->toIso8601String(),
            ])->values(),
        ]]);
    }

    public function update(Request $request): JsonResponse
    {
        $conversation = $this->conversation($request);

        /** @var Team $team */
        $team = Team::query()->findOrFail($conversation->tenant_id);

        $validated = $request->validate([
            'status' => ['sometimes', Rule::in(['open', 'pending', 'closed'])],
            'assigned_user_id' => ['sometimes', 'nullable', 'integer', Rule::in($team->members->modelKeys())],
        ]);

        $conversation->update($validated);

        $this->audit->record('inbox.updated', ActorType::User, $request->user()?->id, subject: $conversation, context: $validated);

        return $this->show($request);
    }

    public function note(Request $request): JsonResponse
    {
        $conversation = $this->conversation($request);

        $validated = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $conversation->notes()->create(['user_id' => $request->user()?->id, 'body' => $validated['body']]);

        return $this->show($request);
    }

    /**
     * Resolved here, inside the tenant context — never through implicit route
     * binding, which may run before the tenant is set.
     */
    private function conversation(Request $request): Conversation
    {
        return Conversation::query()->findOrFail((int) $request->route('conversation'));
    }

    /**
     * @return array<string, mixed>
     */
    private function summary(Conversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'phone' => $this->commerce->maskedPhone($conversation->subject_hash),
            'status' => $conversation->status,
            'reason' => $conversation->reason,
            'assigned_user_id' => $conversation->assigned_user_id,
            'last_message_at' => $conversation->last_message_at->toIso8601String(),
        ];
    }
}
