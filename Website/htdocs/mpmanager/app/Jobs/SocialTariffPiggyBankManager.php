<?php

namespace App\Jobs;

use App\Models\SocialTariff;
use App\Models\SocialTariffPiggyBank;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SocialTariffPiggyBankManager extends AbstractJob
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct(get_class($this));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob()
    {
        //get all social tariffs
        $socialTariffs = SocialTariff::all();

        foreach ($socialTariffs as $socialTariff) {
            $this->chargeClients($socialTariff);
        }
    }


    private function chargeClients(SocialTariff $socialTariff): void
    {
        SocialTariffPiggyBank::query()->
        where('social_tariff_id', $socialTariff->id)
            ->where('savings', '<=', $socialTariff->maximum_stacked_energy)
            ->increment('savings', $socialTariff->daily_allowance);

        // update all that have more then max allowed energy
        SocialTariffPiggyBank::query()->
        where('social_tariff_id', $socialTariff->id)
            ->where('savings', '>', $socialTariff->maximum_stacked_energy)
            ->update(['savings' => $socialTariff->initial_energy_budget]);
    }
}
