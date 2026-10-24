<?php

namespace App\Shared\Logging;

use Illuminate\Log\Logger;
use Monolog\LogRecord;

/**
 * Logging "tap": every channel runs records through the Redactor.
 */
class RedactPersonalData
{
    public function __invoke(Logger $logger): void
    {
        $logger->pushProcessor(fn (LogRecord $record) => $record->with(
            message: Redactor::text($record->message),
            context: Redactor::context($record->context),
            extra: Redactor::context($record->extra),
        ));
    }
}
