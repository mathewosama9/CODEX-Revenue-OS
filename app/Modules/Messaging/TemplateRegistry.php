<?php

namespace App\Modules\Messaging;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Messaging\Meta\MetaGraphClient;
use App\Modules\Messaging\Models\MessageTemplate;
use App\Modules\Messaging\Models\WhatsAppAccount;

/**
 * Keeps our copy of the merchant's templates in sync and maps each one to a
 * purpose in our flows. Category is forced MARKETING for recovery purposes.
 */
class TemplateRegistry
{
    public function __construct(
        private readonly MetaGraphClient $meta,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * Submit our Arabic library (config/whatsapp_templates.php) to the WABA.
     */
    public function submitLibrary(WhatsAppAccount $account): void
    {
        /** @var array<string, array<string, mixed>> $library */
        $library = config('whatsapp_templates.library');

        foreach ($library as $purpose => $definition) {
            $exists = MessageTemplate::query()
                ->where('name', $definition['name'])
                ->where('language', $definition['language'])
                ->exists();

            if ($exists) {
                continue;
            }

            $created = $this->meta->createTemplate($account->access_token, $account->waba_id, [
                'name' => $definition['name'],
                'language' => $definition['language'],
                'category' => 'MARKETING',
                'components' => $definition['components'],
            ]);

            MessageTemplate::create([
                'whatsapp_account_id' => $account->id,
                'external_id' => isset($created['id']) ? (string) $created['id'] : null,
                'name' => $definition['name'],
                'language' => $definition['language'],
                'category' => 'MARKETING',
                'status' => is_string($created['status'] ?? null) ? $created['status'] : 'PENDING',
                'purpose' => $purpose,
                'components' => $definition['components'],
            ]);
        }

        $this->audit->record('whatsapp.templates_submitted', ActorType::System, subject: $account);
    }

    public function sync(WhatsAppAccount $account): void
    {
        foreach ($this->meta->templates($account->access_token, $account->waba_id) as $row) {
            MessageTemplate::query()->updateOrCreate(
                ['name' => (string) $row['name'], 'language' => (string) $row['language']],
                [
                    'whatsapp_account_id' => $account->id,
                    'external_id' => (string) ($row['id'] ?? ''),
                    'category' => strtoupper((string) ($row['category'] ?? '')),
                    'status' => strtoupper((string) ($row['status'] ?? '')),
                    'components' => (array) ($row['components'] ?? []),
                ],
            );
        }
    }

    public function updateStatus(string $name, string $language, string $status): void
    {
        MessageTemplate::query()->where('name', $name)->where('language', $language)->update(['status' => strtoupper($status)]);
    }

    public function updateCategory(string $name, string $language, string $category): void
    {
        MessageTemplate::query()->where('name', $name)->where('language', $language)->update(['category' => strtoupper($category)]);
    }
}
