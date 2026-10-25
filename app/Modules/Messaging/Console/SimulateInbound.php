<?php

namespace App\Modules\Messaging\Console;

use App\Models\Team;
use App\Modules\Messaging\Models\WhatsAppNumber;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Webhooks\WebhookInbox;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Local demos and E2E only: a customer "writes" on WhatsApp. The message goes
 * through the real Meta webhook handler (opt-out, conversation, agent), with
 * the queue run in place so the result is there when the command returns.
 *
 *   php artisan codex:demo-message "بكم العود؟"
 */
class SimulateInbound extends Command
{
    protected $signature = 'codex:demo-message {text} {--team= : workspace slug (default: the demo store)} {--phone=0551234567}';

    protected $description = 'Simulate an inbound WhatsApp message (local only)';

    public function handle(WebhookInbox $inbox, TenantContext $tenants): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Local only.');

            return self::FAILURE;
        }

        config(['queue.default' => 'sync']);

        $team = $this->option('team') === null
            ? Team::query()->where('is_personal', false)->orderBy('id')->first()
            : Team::query()->where('slug', $this->option('team'))->first();
        $phone = PhoneNumber::tryParse((string) $this->option('phone'));

        if ($team === null || $phone === null) {
            $this->error('Unknown workspace or phone.');

            return self::FAILURE;
        }

        $number = $tenants->run($team->id, fn () => WhatsAppNumber::query()->with('account')->where('status', 'active')->first());

        if ($number === null) {
            $this->error('This workspace has no active WhatsApp number.');

            return self::FAILURE;
        }

        $id = 'wamid.demo.'.Str::random(12);
        $inbox->receive('meta', $id, 'messages', ['object' => 'whatsapp_business_account', 'entry' => [[
            'id' => $number->account->waba_id,
            'changes' => [['field' => 'messages', 'value' => [
                'metadata' => ['phone_number_id' => $number->phone_number_id],
                'messages' => [['from' => ltrim($phone->e164, '+'), 'id' => $id, 'type' => 'text', 'text' => ['body' => (string) $this->argument('text')]]],
            ]]],
        ]]]);

        $this->info('Delivered to '.$team->name.'.');

        return self::SUCCESS;
    }
}
