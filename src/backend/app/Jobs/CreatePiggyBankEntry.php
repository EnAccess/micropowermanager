<?php

namespace App\Jobs;

use App\Models\Meter\Meter;

class CreatePiggyBankEntry extends AbstractJob {
    public function __construct(private Meter $meter) {
        parent::__construct(get_class($this));
    }

    public function executeJob() {
        if ($socialTariff = $this->meter->tariff()->first()->socialTariff) {
            $this->meter->socialTariffPiggyBank()->create(
                [
                    'savings' => $socialTariff->initial_energy_budget,
                    'social_tariff_id' => $socialTariff->id,
                ]
            );
        }
    }
}
