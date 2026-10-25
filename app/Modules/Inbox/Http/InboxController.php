<?php

namespace App\Modules\Inbox\Http;

use App\Models\Team;
use App\Modules\Commerce\Commerce;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Inbox\Inbox;
use App\Modules\Inbox\Models\Conversation;
use App\Modules\Inbox\Models\ConversationNote;
use App\Modules\Messaging\Messaging;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InboxController
{
    public function __construct(
        private readonly Commerce $commerce,
        private readonly Messaging $messaging,
        private readonly AuditLogger $audit,
        private readonly Inbox $inbox,
    ) {}

    private const PAGE_SIZE = 50;

    /**
     * Newest first, 50 at a time; `meta.next_cursor` fetches the next page
     * (review L1). Phones are masked in one query for the whole page.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['open', 'pending', 'closed', 'all'])],
            'cursor' => ['nullable', 'string', 'max:64', 'regex:/^\d+:\d+$/'],
        ]);

        $status = $validated['status'] ?? 'open';
        $query = Conversation::query()
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if (isset($validated['cursor'])) {
            [$timestamp, $id] = array_map('intval', explode(':', $validated['cursor']));
            $at = CarbonImmutable::createFromTimestampUTC($timestamp);

            $query->where(fn ($query) => $query->where('last_message_at', '<', $at)
                ->orWhere(fn ($query) => $query->where('last_message_at', $at)->where('id', '<', $id)));
        }

        $page = $query->limit(self::PAGE_SIZE + 1)->get();
        $more = $page->count() > self::PAGE_SIZE;
        $page = $page->take(self::PAGE_SIZE);
        $phones = $this->commerce->maskedPhones($page->pluck('subject_hash')->all());
        $last = $page->last();

        return response()->json([
            'data' => $page->map(fn (Conversation $conversation) => $this->summary($conversation, $phones))->values(),
            'meta' => ['next_cursor' => $more && $last !== null ? $last->last_message_at->getTimestamp().':'.$last->id : null],
        ]);
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

    /** Takeover / hand back to the AI (US-E12-03), both audited by the Inbox. */
    public function takeover(Request $request): JsonResponse
    {
        $this->inbox->setAiPaused($this->conversation($request)->id, true, (int) $request->user()?->id);

        return $this->show($request);
    }

    public function handBack(Request $request): JsonResponse
    {
        $this->inbox->setAiPaused($this->conversation($request)->id, false, (int) $request->user()?->id);

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
     * @param  array<string, string>|null  $phones  pre-loaded masked phones (lists); null looks one up
     * @return array<string, mixed>
     */
    private function summary(Conversation $conversation, ?array $phones = null): array
    {
        return [
            'id' => $conversation->id,
            'phone' => $phones === null ? $this->commerce->maskedPhone($conversation->subject_hash) : ($phones[$conversation->subject_hash] ?? null),
            'status' => $conversation->status,
            'reason' => $conversation->reason,
            'assigned_user_id' => $conversation->assigned_user_id,
            'ai_paused' => (bool) $conversation->ai_paused,
            'last_message_at' => $conversation->last_message_at->toIso8601String(),
        ];
    }
}
