<?php

namespace App\Modules\SalesAgent\Tools;

use App\Modules\SalesAgent\HandoffReason;

/**
 * Server-side facts of one run, shared by its tools. The model never sets
 * them: the customer and the tenant come from the job, not from arguments.
 */
final class RunContext
{
    public ?HandoffReason $handoff = null;

    public ?string $handoffNote = null;

    public bool $policyMissing = false;

    public function __construct(public readonly string $subjectHash) {}
}
