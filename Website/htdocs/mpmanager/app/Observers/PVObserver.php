<?php

namespace App\Observers;

use App\Helpers\PowerConverter;
use App\Models\PV;

/**
 * Class PVCreated.
 */
class PVObserver
{
    /**
     * @var PV
     */
    private $pv;

    public function __construct(PV $pv)
    {
        $this->pv = $pv;
    }

    /**
     * @return void
     */
    public function created()
    {
        // get last 2 rows to calculate the difference between them
        $p = $this->pv->latest()->take(2)->get();

        if (count($p) === 1) {
            return;
        }
        $lastDailyPower = PowerConverter::convert($p[0]->daily, $p[0]->daily_unit, 'Wh');
        $prevDailyPower = PowerConverter::convert($p[1]->daily, $p[1]->daily_unit, 'Wh');

        $p[0]->new_generated_energy = $lastDailyPower - $prevDailyPower;
        $p[0]->save();
    }
}
