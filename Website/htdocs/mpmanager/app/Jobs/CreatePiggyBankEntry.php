<?php

namespace App\Jobs;

use App\Models\Meter\MeterParameter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreatePiggyBankEntry extends AbstractJob
{


    public function __construct(private MeterParameter $meterParameter)
    {
    }

    public function handle()
    {
        echo "create piggy bank " . $this->meterParameter->tariff->id . "\n";
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
