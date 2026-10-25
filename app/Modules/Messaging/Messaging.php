<?php

namespace App\Modules\Messaging;

use App\Modules\Messaging\Models\Message;
use App\Modules\Messaging\Models\MessageTemplate;
use App\Modules\Messaging\Models\WhatsAppNumber;
use Carbon\CarbonImmutable;

/**
 * Public read API of M03 for other modules.
 */
class Messaging
{
    /**
     * @return array{connected: bool, quality_ok: bool, templates: array<string, bool>}
     */
    public function readiness(): array
    {
        $number = WhatsAppNumber::query()->where('status', 'active')->first();

        $approved = MessageTemplate::query()
            ->whereNotNull('purpose')
            ->where('status', 'APPROVED')
            ->where('category', 'MARKETING')
            ->pluck('purpose')
            ->all();

        return [
            'connected' => $number !== null,
            'quality_ok' => $number !== null && $number->quality_rating->allowsMarketing(),
            'templates' => array_fill_keys(array_map('strval', $approved), true),
        ];
    }

    /**
     * The latest messages with one customer, oldest first (inbox read view).
     *
     * @return list<array{id: int, direction: string, body: string|null, template: string|null, status: string, source: string|null, at: string}>
     */
    public function conversation(string $subjectHash, int $limit = 50): array
    {
        return array_values(Message::query()
            ->where('subject_hash', $subjectHash)
            ->latest('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn (Message $message) => [
                'id' => $message->id,
                'direction' => $message->direction,
                'body' => $message->body,
                'template' => $message->template_name,
                'status' => $message->status->value,
                'source' => is_string($message->context['source'] ?? null) ? $message->context['source'] : null,
                'at' => $message->created_at->toIso8601String(),
            ])
            ->all());
    }

    /** The newest customer message: an agent run for an older one is superseded. */
    public function latestInboundId(string $subjectHash): ?int
    {
        $id = Message::query()->where('subject_hash', $subjectHash)->where('direction', Message::INBOUND)->max('id');

        return $id === null ? null : (int) $id;
    }

    public function forgetSubject(string $subjectHash): int
    {
        return Message::query()->where('subject_hash', $subjectHash)->update(['body' => null]);
    }

    /** Retention: message text older than the window is dropped; metadata stays. */
    public function purgeBodiesBefore(CarbonImmutable $cutoff): int
    {
        return Message::query()->whereNotNull('body')->where('created_at', '<', $cutoff)->update(['body' => null]);
    }

    public function canSendMarketing(string $purpose): bool
    {
        $readiness = $this->readiness();

        return $readiness['connected'] && $readiness['quality_ok'] && ($readiness['templates'][$purpose] ?? false);
    }
}
