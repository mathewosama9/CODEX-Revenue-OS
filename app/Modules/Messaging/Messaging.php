<?php

namespace App\Modules\Messaging;

use App\Modules\Messaging\Models\MessageTemplate;
use App\Modules\Messaging\Models\WhatsAppNumber;

/**
 * Public read API of M03 for other modules.
 */
class Messaging
{
    /**
     * @return array{connected: bool, quality_ok: bool, templates: array<string, bool>}
     */
    public function readiness(): array
    {
        $number = WhatsAppNumber::query()->where('status', 'active')->first();

        $approved = MessageTemplate::query()
            ->whereNotNull('purpose')
            ->where('status', 'APPROVED')
            ->where('category', 'MARKETING')
            ->pluck('purpose')
            ->all();

        return [
            'connected' => $number !== null,
            'quality_ok' => $number !== null && $number->quality_rating->allowsMarketing(),
            'templates' => array_fill_keys(array_map('strval', $approved), true),
        ];
    }

    public function canSendMarketing(string $purpose): bool
    {
        $readiness = $this->readiness();

        return $readiness['connected'] && $readiness['quality_ok'] && ($readiness['templates'][$purpose] ?? false);
    }
}
