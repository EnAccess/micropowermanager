<?php

namespace App\Plugins\SteamaMeter\Services;

use App\Models\Tariff;
use App\Plugins\SteamaMeter\Models\SteamaTariff;

class SteamaTariffService {
    public function __construct(private readonly SteamaTariff $tariff, private readonly Tariff $meterTariff) {}

    /**
     * This function uses one time on installation of the package.
     */
    public function createTariff(): Tariff {
        $meterTariff = $this->meterTariff->newQuery()->where('name', 'Steama External Tariff')->first();
        if (!$meterTariff) {
            $meterTariff = $this->meterTariff->newQuery()->create([
                'name' => 'Steama External Tariff',
                'price' => 0,
                'currency' => config('steama.currency'),
            ]);
            $this->tariff->newQuery()->create([
                'mpm_tariff_id' => $meterTariff->id,
            ]);
        }

        return $meterTariff;
    }
}
