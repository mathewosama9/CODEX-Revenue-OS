<?php

namespace App\Shared\Webhooks;

enum WebhookStatus: string
{
    case Received = 'received';
    case Processed = 'processed';

    /** No handler is registered for this event type yet. */
    case Unhandled = 'unhandled';

    case Failed = 'failed';
}
