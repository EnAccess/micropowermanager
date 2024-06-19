<?php

namespace App\Observers;

use App\Helpers\PowerConverter;
use App\Models\Battery;

class BatteryObserver
{
    /**
     * @var Battery
     */
    private $battery;

    public function __construct(Battery $battery)
    {
        $this->battery = $battery;
    }

    public function created(): void
    {
        $b = $this->battery->newQuery()->latest()->take(2)->get();

        if (count($b) === 1) {
            return;
        }

        $lastDischargedEnergy = PowerConverter::convert($b[0]->d_total, $b[0]->d_total_unit, 'Wh');
        $prevDischargedEnergy = PowerConverter::convert($b[1]->d_total, $b[1]->d_total_unit, 'Wh');
        $lastChargedEnergy = PowerConverter::convert($b[0]->c_total, $b[0]->c_total_unit, 'Wh');
        $prevChargedEnergy = PowerConverter::convert($b[1]->c_total, $b[1]->c_total_unit, 'Wh');

        $b[0]->c_newly_energy = $lastChargedEnergy - $prevChargedEnergy;
        $b[0]->c_newly_energy_unit = 'Wh';
        $b[0]->d_newly_energy = $lastDischargedEnergy - $prevDischargedEnergy;
        $b[0]->d_newly_energy_unit = 'Wh';

        $b[0]->save();
    }
}
