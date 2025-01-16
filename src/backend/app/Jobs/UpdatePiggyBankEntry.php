<?php

namespace App\Jobs;

use App\Models\Meter\Meter;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdatePiggyBankEntry extends AbstractJob {
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * @var Meter
     */
    private $meter;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Meter $meter) {
        $this->meter = $meter;
        parent::__construct(get_class($this));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function executeJob() {
        $socialTariff = $this->meter->tariff()->first()->socialTariff;
        if (!$socialTariff) {
            echo "meter has no social tariff should be deleted \n";
            // meter parameter has no social tariff

            // delete socialPiggyBankEntryForThatCustomer

            return;
        }
        if ($piggyBank = $this->meter->socialTariffPiggyBank) {
            // update bank entry
            $piggyBank->savings = $socialTariff->initial_energy_budget;
        } else {
            CreatePiggyBankEntry::dispatch($this->meter)
                ->allOnConnection('redis')
                ->onQueue(config('services.queues.misc'));
        }
    }
}
