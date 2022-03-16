<?php

namespace App\Jobs;

use App\Models\Meter\MeterParameter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreatePiggyBankEntry implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var MeterParameter
     */
    private $meterParameter;

    /**
     * Create a new job instance.
     *
     * @param MeterParameter $meterParameter
     */
    public function __construct(MeterParameter $meterParameter)
    {
        $this->meterParameter = $meterParameter;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
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


            echo "piggy bank account created \n";
        }
    }
}
