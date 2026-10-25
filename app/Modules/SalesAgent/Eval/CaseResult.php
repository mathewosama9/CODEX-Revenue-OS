<?php

namespace App\Modules\SalesAgent\Eval;

final readonly class CaseResult
{
    public const PASSED = 'passed';

    public const FAILED = 'failed';

    /** Offline run without a script: only a live model can judge it. */
    public const LIVE_ONLY = 'live_only';

    /**
     * @param  list<string>  $problems
     */
    public function __construct(
        public GoldenCase $case,
        public string $status,
        public array $problems = [],
        public ?string $reply = null,
        public int $costUsdMicros = 0,
    ) {}
}
