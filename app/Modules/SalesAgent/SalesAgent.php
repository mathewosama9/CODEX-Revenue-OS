<?php

namespace App\Modules\SalesAgent;

use App\Models\Team;
use App\Modules\AiGateway\AiGateway;
use App\Modules\AiGateway\AiStatus;
use App\Modules\AiGateway\Data\AiReply;
use App\Modules\AiGateway\Data\AiRequest;
use App\Modules\AiGateway\Data\ChatTurn;
use App\Modules\Billing\UsageMeter;
use App\Modules\Commerce\Commerce;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Compliance\QuietHours;
use App\Modules\Inbox\Data\ConversationSnapshot;
use App\Modules\Inbox\Inbox;
use App\Modules\Messaging\MessageSender;
use App\Modules\Messaging\Messaging;
use App\Modules\SalesAgent\Models\AgentRun;
use App\Modules\SalesAgent\Models\AgentSettings;
use App\Modules\SalesAgent\Models\ReplyDraft;
use App\Modules\SalesAgent\Tools\RunContext;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Tenancy\WorkspaceLocale;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * One agent, one customer message (M06, 09 §9.11.1):
 *
 *   off/paused/opted out → nothing · handoff rules → person ·
 *   AiGateway + tools → validator (one retry) → draft (Assisted) or send
 *   (Scoped Autonomous) · any doubt → person.
 *
 * Safe failure is silence plus a human (09 §9.14): the agent never sends
 * a reply it could not check.
 */
class SalesAgent
{
    private const HISTORY = 20;

    private const RETRY_DEADLINE_SECONDS = 30;

    public function __construct(
        private readonly TenantContext $tenants,
        private readonly AgentControls $controls,
        private readonly Toolbox $toolbox,
        private readonly HandoffRules $rules,
        private readonly AiGateway $gateway,
        private readonly Inbox $inbox,
        private readonly Messaging $messaging,
        private readonly MessageSender $sender,
        private readonly Commerce $commerce,
        private readonly ReplyConsent $replyConsent,
        private readonly QuietHours $quietHours,
        private readonly UsageMeter $usage,
        private readonly AuditLogger $audit,
        private readonly BlockRateMonitor $monitor,
        private readonly WorkspaceLocale $locale,
    ) {}

    public function respond(int $conversationId, int $inboundMessageId): ?AgentRun
    {
        $settings = $this->controls->settings();
        $conversation = $this->inbox->conversation($conversationId);

        if ($settings->mode === AgentMode::Off || $conversation === null) {
            return null;
        }

        try {
            $run = AgentRun::create([
                'conversation_id' => $conversationId,
                'inbound_message_id' => $inboundMessageId,
                'mode' => $settings->mode->value,
                'status' => 'running',
                'trace' => [],
            ]);
        } catch (UniqueConstraintViolationException) {
            return null; // This message already has a run (queue retry or duplicate webhook).
        }

        $skip = $this->skipReason($conversation, $inboundMessageId);

        if ($skip !== null) {
            return $this->finish($run, AgentRun::SKIPPED, $skip);
        }

        // An image or a sticker: a person looks. Never answer an older question again (review: correctness #3).
        if ($this->messaging->inboundText($inboundMessageId) === null) {
            return $this->finish($run, AgentRun::SKIPPED, 'no_text');
        }

        // The customer wrote again: an older draft answers an older question.
        $this->supersedePendingDrafts($conversationId);

        $history = $this->history($conversation->subjectHash);
        $latest = end($history);

        if ($latest === false) {
            return $this->finish($run, AgentRun::SKIPPED, 'no_text'); // e.g. an image or a sticker: a person looks.
        }

        [$reply, $reason] = $this->decide($run, $conversation, $settings, $history);

        // A member may have taken over while the model was thinking (US-E12-03).
        if ($this->inbox->conversation($conversationId)?->aiPaused === true) {
            return $this->finish($run, AgentRun::SKIPPED, 'paused');
        }

        if ($reason !== null || $reply === null) {
            return $this->handOff($run, $conversation, $settings, $reason ?? HandoffReason::EmptyReply);
        }

        return $settings->mode === AgentMode::ScopedAutonomous
            ? $this->send($run, $conversation, $reply->text)
            : $this->draft($run, $conversation, $reply->text);
    }

    /**
     * The same decision as a live run, with nothing saved or sent: what the
     * golden set measures (US-E10-05).
     *
     * @param  list<ChatTurn>  $history
     */
    public function evaluate(array $history, string $subjectHash): Decision
    {
        $settings = $this->controls->settings();
        $run = new AgentRun(['trace' => [], 'attempts' => 0, 'validator_blocks' => 0, 'cost_usd_micros' => 0]);
        $conversation = new ConversationSnapshot(0, $subjectHash, null, 'open', 'eval', false, CarbonImmutable::now());

        [$reply, $reason] = $this->decide($run, $conversation, $settings, $history);

        return new Decision(
            text: $reply?->text,
            handoff: $reply === null ? ($reason ?? HandoffReason::EmptyReply) : null,
            tools: array_values(array_unique(array_column($run->trace, 'tool'))),
            attempts: $run->attempts,
            validatorBlocks: $run->validator_blocks,
            costUsdMicros: $run->cost_usd_micros,
        );
    }

    /**
     * Rules first, then the model with its guards.
     *
     * @param  list<ChatTurn>  $history  ends with the customer
     * @return array{0: AiReply|null, 1: HandoffReason|null}
     */
    private function decide(AgentRun $run, ConversationSnapshot $conversation, AgentSettings $settings, array $history): array
    {
        $latest = end($history);
        $cart = $this->commerce->openCartFor($conversation->subjectHash);
        $reason = $this->rules->check($latest === false ? '' : $latest->text, $cart?->totalHalalas, $this->controls->handoffCartMinor($settings));

        if ($reason !== null) {
            return [null, $reason];
        }

        [$reply, $reason] = $this->ask($run, $conversation, $settings, $history);

        return [$reply, $reason];
    }

    private function skipReason(ConversationSnapshot $conversation, int $inboundMessageId): ?string
    {
        return match (true) {
            $conversation->aiPaused => 'paused',
            $this->messaging->latestInboundId($conversation->subjectHash) !== $inboundMessageId => 'superseded',
            $conversation->phone === null => 'no_phone',
            // An opt-out always silences the AI; people still can answer (see ReplyConsent).
            $this->replyConsent->refusal($conversation->phone) !== null => $this->replyConsent->refusal($conversation->phone),
            default => null,
        };
    }

    /**
     * The model, the guards, and one retry if a number did not check out.
     *
     * @param  list<ChatTurn>  $history
     * @return array{0: AiReply|null, 1: HandoffReason|null}
     */
    private function ask(AgentRun $run, ConversationSnapshot $conversation, AgentSettings $settings, array $history): array
    {
        $prompt = SystemPrompt::for($this->storeName(), $this->locale->currency());
        $instructions = $prompt;
        $trace = [];
        $started = hrtime(true);

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $context = new RunContext($conversation->subjectHash);
            $reply = $this->gateway->converse(new AiRequest(
                purpose: 'sales_agent',
                instructions: $instructions,
                history: $history,
                tools: $this->toolbox->forRun($context),
                dailyCapUsdMicros: $settings->daily_cap_usd_micros,
                conversationRef: 'conv:'.$conversation->id,
                subjectHash: $conversation->subjectHash,
                knownNames: $this->knownNames(),
                trustedTexts: [$this->storeName()],
            ));

            $run->attempts = $attempt;
            $run->cost_usd_micros += $reply->costUsdMicros;

            foreach ($reply->tools as $tool) {
                $trace[] = array_filter([
                    'attempt' => $attempt,
                    'tool' => $tool->name,
                    // Ids only, capped: the model could put customer text in an argument (ADR-008).
                    'product_id' => is_scalar($tool->arguments['product_id'] ?? null) ? mb_substr((string) $tool->arguments['product_id'], 0, 64) : null,
                    'topic' => is_scalar($tool->arguments['topic'] ?? null) ? mb_substr((string) $tool->arguments['topic'], 0, 20) : null,
                    'failed' => $tool->failed ?: null,
                ], fn ($value) => $value !== null);
            }

            $run->trace = $trace;

            $reason = match (true) {
                $reply->status === AiStatus::CapReached => HandoffReason::CapReached,
                $reply->status !== AiStatus::Ok => HandoffReason::ProviderUnavailable,
                $context->handoff !== null => $context->handoff,
                array_filter($reply->tools, fn ($tool) => $tool->failed) !== [] => HandoffReason::ToolFailed,
                $context->policyMissing => HandoffReason::PolicyMissing,
                // Rule 9 first: a reply that talks discounts is never retried, it goes to a person.
                $this->rules->mentionsDiscount($reply->text) => HandoffReason::DiscountRequest,
                default => null,
            };

            if ($reason !== null) {
                return [null, $reason];
            }

            if ($reply->validation->passed()) {
                return $reply->ok() ? [$reply, null] : [null, HandoffReason::EmptyReply];
            }

            $run->validator_blocks++;

            // No second try once the first one was slow: the job must finish and hand off (review: correctness #6).
            if (hrtime(true) - $started > self::RETRY_DEADLINE_SECONDS * 1_000_000_000) {
                break;
            }

            $trace[] = ['attempt' => $attempt, 'blocked_numbers' => $reply->validation->unmatched];
            $run->trace = $trace;
            $instructions = $prompt.SystemPrompt::retryNote($reply->validation->unmatched);
        }

        return [null, HandoffReason::ValidatorFailed];
    }

    private function draft(AgentRun $run, ConversationSnapshot $conversation, string $text): AgentRun
    {
        // Seconds have passed: a newer message has its own run, a takeover wants silence (review: correctness #4).
        $fresh = $this->inbox->conversation($conversation->id);

        if ($fresh === null || $fresh->aiPaused || $this->messaging->latestInboundId($fresh->subjectHash) !== $run->inbound_message_id) {
            return $this->finish($run, AgentRun::SKIPPED, 'superseded');
        }

        ReplyDraft::create([
            'agent_run_id' => $run->id,
            'conversation_id' => $conversation->id,
            'body' => $text,
            'status' => ReplyDraft::PENDING,
        ]);

        $this->usage->increment(UsageMeter::AI_DRAFTS);
        $this->inbox->handOff($conversation->id, 'ai_draft');

        return $this->finish($run, AgentRun::DRAFTED);
    }

    /**
     * Scoped Autonomous only. Inside the 24-hour window, a checked reply is a
     * free-form service message (US-E11-07: also at night, since it passed).
     */
    private function send(AgentRun $run, ConversationSnapshot $conversation, string $text): AgentRun
    {
        if (! $this->maySendNow($run, $conversation)) {
            return $this->finish($run, AgentRun::SKIPPED, 'stopped_before_send');
        }

        if ($conversation->phone === null || ! $conversation->windowOpen(CarbonImmutable::now())) {
            return $this->handOff($run, $conversation, $this->controls->settings(), HandoffReason::AgentHandoff);
        }

        $result = $this->sender->sendServiceText($conversation->phone, $text, ['source' => 'ai_autonomous', 'agent_run_id' => $run->id]);

        $this->audit->record('ai.reply_sent', ActorType::AiAgent, subject: $run, context: ['agent_run_id' => $run->id, 'outcome' => $result->outcome->value]);

        return $result->accepted()
            ? $this->finish($run, AgentRun::SENT)
            : $this->handOff($run, $conversation, $this->controls->settings(), HandoffReason::ProviderUnavailable);
    }

    private function handOff(AgentRun $run, ConversationSnapshot $conversation, AgentSettings $settings, HandoffReason $reason): AgentRun
    {
        $this->inbox->handOff($conversation->id, $reason->conversationReason());

        // Night, autonomous: tell the customer a person answers in the morning (09 §9.11.3).
        if ($settings->mode === AgentMode::ScopedAutonomous
            && $conversation->phone !== null
            && $conversation->windowOpen(CarbonImmutable::now())
            && $this->quietHours->isQuiet(CarbonImmutable::now())
            && ! $this->messaging->sentSince($conversation->subjectHash, 'ai_night_handoff', CarbonImmutable::now()->subHours(12))
            && $this->maySendNow($run, $conversation)) {
            $this->sender->sendServiceText($conversation->phone, (string) config('whatsapp_templates.ai_night_handoff'), ['source' => 'ai_night_handoff', 'agent_run_id' => $run->id]);
        }

        return $this->finish($run, AgentRun::HANDED_OFF, $reason->value);
    }

    /**
     * The model call can take seconds. Right before anything leaves on its
     * own, check again: the kill switch, a takeover, an opt-out, a newer
     * customer message (review: security #2).
     */
    /** The job died (timeout, crash): the run must not stay "running"; a person takes it. */
    public function abandon(int $inboundMessageId): void
    {
        $run = AgentRun::query()->where('inbound_message_id', $inboundMessageId)->where('status', 'running')->first();

        if ($run !== null) {
            $this->inbox->handOff($run->conversation_id, HandoffReason::ProviderUnavailable->conversationReason());
            $run->update(['status' => AgentRun::HANDED_OFF, 'handoff_reason' => HandoffReason::ProviderUnavailable->value]);
        }
    }

    private function maySendNow(AgentRun $run, ConversationSnapshot $conversation): bool
    {
        $fresh = $this->inbox->conversation($conversation->id);

        return $this->controls->settings()->mode === AgentMode::ScopedAutonomous
            && $fresh !== null
            && ! $fresh->aiPaused
            && $fresh->phone !== null
            && $this->replyConsent->allows($fresh->phone)
            && $this->messaging->latestInboundId($fresh->subjectHash) === $run->inbound_message_id;
    }

    private function finish(AgentRun $run, string $status, ?string $reason = null): AgentRun
    {
        $run->status = $status;
        $run->handoff_reason = $reason;
        $run->save();

        if ($run->attempts > 0) {
            $this->monitor->check($this->controls->settings());
        }

        return $run;
    }

    private function supersedePendingDrafts(int $conversationId): void
    {
        ReplyDraft::query()
            ->where('conversation_id', $conversationId)
            ->where('status', ReplyDraft::PENDING)
            ->update(['status' => 'superseded', 'decided_at' => CarbonImmutable::now()]);
    }

    /**
     * @return list<ChatTurn>
     */
    private function history(string $subjectHash): array
    {
        $turns = [];

        foreach ($this->messaging->conversation($subjectHash, self::HISTORY) as $message) {
            $text = $message['body'] ?? ($message['template'] === null ? null : '[template: '.$message['template'].']');

            if ($text === null || trim($text) === '') {
                continue;
            }

            $turns[] = $message['direction'] === 'inbound' ? ChatTurn::customer($text) : ChatTurn::assistant($text);
        }

        // The model must answer the customer: drop trailing replies after their last message.
        while ($turns !== [] && end($turns)->role !== ChatTurn::CUSTOMER) {
            array_pop($turns);
        }

        return $turns;
    }

    /**
     * Names we know and must hide from the model (ADR-008): the team's own
     * members, who sign their replies. The store keeps no customer names
     * (Salla gives us phones only); a name a customer types about themself is
     * a known gap, written up in ADR-008.
     *
     * @return list<string>
     */
    private function knownNames(): array
    {
        $team = Team::query()->find($this->tenants->require());

        if ($team === null) {
            return [];
        }

        return array_values(array_unique(array_merge(...array_map(
            fn (string $name) => [$name, ...array_filter(explode(' ', $name), fn ($part) => mb_strlen($part) >= 3)],
            $team->members->pluck('name')->map(fn ($name) => (string) $name)->all(),
        ))));
    }

    private function storeName(): string
    {
        return (string) Team::query()->whereKey($this->tenants->require())->value('name');
    }
}
