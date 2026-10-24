<?php

namespace App\Shared\Webhooks;

interface WebhookHandler
{
    /**
     * Resolve the tenant from the provider's mapping (store id, phone_number_id),
     * map the payload to the internal model, and emit domain events.
     */
    public function handle(WebhookEvent $event): void;
}
