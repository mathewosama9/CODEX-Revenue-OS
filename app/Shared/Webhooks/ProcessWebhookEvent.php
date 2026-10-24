<?php

namespace App\Shared\Webhooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

/**
 * Hands a stored webhook to the module that owns its provider. Handlers are
 * registered in config('codex.webhook_handlers') as provider => class, and are
 * responsible for resolving the tenant from their own mapping table.
 */
class ProcessWebhookEvent implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function __construct(public int $webhookEventId) {}

    public function handle(): void
    {
        $event = WebhookEvent::findOrFail($this->webhookEventId);

        if ($event->status === WebhookStatus::Processed) {
            return;
        }

        $event->increment('attempts');

        /** @var class-string<WebhookHandler>|null $handlerClass */
        $handlerClass = config("codex.webhook_handlers.{$event->provider}");

        if ($handlerClass === null) {
            $event->update(['status' => WebhookStatus::Unhandled]);

            return;
        }

        try {
            app($handlerClass)->handle($event);
        } catch (Throwable $exception) {
            $event->update(['status' => WebhookStatus::Failed, 'error' => $exception::class.': '.$exception->getMessage()]);

            throw $exception;
        }

        $event->update(['status' => WebhookStatus::Processed, 'processed_at' => now(), 'error' => null]);
    }
}
