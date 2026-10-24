<?php

namespace App\Modules\Recovery;

use App\Modules\Commerce\Commerce;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Measurement\ExperimentAssigner;
use App\Modules\Measurement\MarginLookup;
use App\Modules\Messaging\Messaging;

/**
 * Recovery cannot be switched on until every item is green (06 S1 step 8,
 * US-E18-05). The holdout is part of the checklist, not an option.
 */
class GoLiveChecklist
{
    public function __construct(
        private readonly Commerce $commerce,
        private readonly Messaging $messaging,
        private readonly MarginLookup $margins,
        private readonly ExperimentAssigner $assigner,
        private readonly ConsentGate $consent,
    ) {}

    /**
     * @return array<string, bool>
     */
    public function items(): array
    {
        $readiness = $this->messaging->readiness();

        return [
            'store_connected' => $this->commerce->isStoreActive(),
            'whatsapp_connected' => $readiness['connected'],
            'number_quality_ok' => $readiness['quality_ok'],
            'first_template_approved' => $readiness['templates']['cart_recovery_1'] ?? false,
            'margins_entered' => $this->margins->resolve(null)['source'] !== 'assumed' || $this->margins->currentByCategory() !== [],
            'holdout_active' => $this->assigner->experiment()->holdout_bps >= 500,
            'consent_source_ready' => $this->consent->hasAnyGrant(),
        ];
    }

    public function passes(): bool
    {
        return ! in_array(false, $this->items(), true);
    }
}
