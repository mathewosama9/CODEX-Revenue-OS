<?php

namespace App\Modules\SalesAgent\Console;

use App\Modules\SalesAgent\Eval\CaseResult;
use App\Modules\SalesAgent\Eval\GoldenSetRunner;
use Illuminate\Console\Command;

/**
 * php artisan ai:eval            offline: scripted model, no key, no cost (CI)
 * php artisan ai:eval --live     the real model: costs money, needs the founder's approval
 */
class RunGoldenSet extends Command
{
    protected $signature = 'ai:eval {--live : Call the real model (paid)} {--yes : Skip the cost confirmation}';

    protected $description = 'Run the golden set against the sales agent (US-E10-05)';

    public function handle(GoldenSetRunner $runner): int
    {
        $live = (bool) $this->option('live');

        if ($live && ! $this->option('yes') && ! $this->confirm('This calls '.config('codex.ai.model').' for every case and costs real money. Continue?')) {
            return self::FAILURE;
        }

        ['results' => $results, 'run' => $run] = $runner->run($live, function (CaseResult $result) {
            $mark = match ($result->status) {
                CaseResult::PASSED => '<info>✓</info>',
                CaseResult::FAILED => '<error>✗</error>',
                default => '<comment>·</comment>',
            };
            $this->line("{$mark} {$result->case->id} ".implode('; ', $result->problems));
        });

        $liveOnly = count(array_filter($results, fn (CaseResult $r) => $r->status === CaseResult::LIVE_ONLY));

        $this->newLine();
        $this->line("Passed {$run->passed}/{$run->cases}, price & stock {$run->price_passed}/{$run->price_cases}".($liveOnly > 0 ? ", {$liveOnly} need --live" : ''));
        $this->line(sprintf('Cost: $%.4f (%s, prompt %s)', $run->cost_usd_micros / 1_000_000, $run->model, $run->prompt_version));

        if ($run->blocking_failure) {
            $this->error('Blocking: a price or stock case failed. Do not merge.');

            return self::FAILURE;
        }

        return $run->passed === $run->cases ? self::SUCCESS : self::FAILURE;
    }
}
