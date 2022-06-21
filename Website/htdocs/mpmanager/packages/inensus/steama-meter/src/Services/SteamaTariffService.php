<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\Meter\MeterTariff;
use Inensus\SteamaMeter\Models\SteamaTariff;

class SteamaTariffService
{
    private $tariff;
    private $meterTariff;

    public function __construct(SteamaTariff $steamaTariffModel, MeterTariff $meterTariffModel)
    {
        $this->tariff = $steamaTariffModel;
        $this->meterTariff = $meterTariffModel;
    }

    /**
     * This function uses one time on installation of the package.
     *
     */
    public function createTariff()
    {
        $meterTariff = $this->meterTariff->newQuery()->where('name', 'Steama External Tariff')->first();
        if (!$meterTariff) {
            $meterTariff = $this->meterTariff->newQuery()->create([
                'name' => 'Steama External Tariff',
                'price' => 0,
                'total_price' => 0,
                'currency' => config('steama.currency')
            ]);
            $this->tariff->newQuery()->create([
                'mpm_tariff_id' => $meterTariff->id
            ]);
        }


         return $meterTariff;
    }
}
