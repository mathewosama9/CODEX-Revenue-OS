<?php

namespace App\Shared\Webhooks;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Records an inbound event exactly once and queues its processing after commit.
 * The HTTP request does no business logic (09 §9.7.2).
 */
class WebhookInbox
{
    /**
     * @param  array<string, mixed>  $payload
     * @return bool false when the event was already received (a retry or duplicate)
     */
    public function receive(string $provider, string $externalEventId, string $eventType, array $payload): bool
    {
        try {
            // Its own (nested) transaction = a savepoint. In Postgres a unique violation
            // aborts the surrounding transaction; the savepoint keeps it usable.
            $event = DB::transaction(fn () => WebhookEvent::create([
                'provider' => $provider,
                'external_event_id' => $externalEventId,
                'event_type' => $eventType,
                'payload' => $payload,
                'status' => WebhookStatus::Received,
            ]));
        } catch (UniqueConstraintViolationException) {
            return false;
        }

        ProcessWebhookEvent::dispatch($event->id)->afterCommit();

        return true;
    }
}
