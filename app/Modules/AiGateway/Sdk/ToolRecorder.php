<?php

namespace App\Modules\AiGateway\Sdk;

use App\Modules\AiGateway\Data\ToolOutcome;

/**
 * Collects the tool outcomes of one turn, in call order.
 */
final class ToolRecorder
{
    /** @var list<ToolOutcome> */
    public array $outcomes = [];

    public function add(ToolOutcome $outcome): void
    {
        $this->outcomes[] = $outcome;
    }
}
