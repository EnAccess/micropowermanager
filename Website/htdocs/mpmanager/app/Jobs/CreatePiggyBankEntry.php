<?php

namespace App\Jobs;

use App\Models\Meter\MeterParameter;

class CreatePiggyBankEntry extends AbstractJob
{
    public function __construct(private MeterParameter $meterParameter)
    {
        parent::__construct(get_class($this));
    }

    public function executeJob()
    {
        if ($socialTariff = $this->meterParameter->tariff()->first()->socialTariff) {
            $this->meterParameter->socialTariffPiggyBank()->create(
                [
                'savings' => $socialTariff->initial_energy_budget,
                'social_tariff_id' => $socialTariff->id,
                ]
            );
        }
    }

}
