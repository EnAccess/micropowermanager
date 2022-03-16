<?php

namespace App\Observers;

use App\Jobs\TariffPricingComponentsCalculator;
use App\Models\AccessRate\AccessRate;
use App\Models\Meter\MeterTariff;
use App\Models\SocialTariff;
use App\Models\TimeOfUsage;

class MeterTariffObserver
{

    public function created(MeterTariff $tariff): void
    {
        if ($accessRate = request()->input('access_rate')) {
            AccessRate::create(
                [
                    'tariff_id' => $tariff->id,
                    'amount' => $accessRate['access_rate_amount'],
                    'period' => $accessRate['access_rate_period'],
                ]
            );
        }
        if ($social = request()->input('social_tariff')) {
            // create social tariff for the given tariff
            if (
                $social['daily_allowance'] !== null & $social['price']
                && $social['initial_energy_budget']
                && $social['maximum_stacked_energy']
            ) {
                SocialTariff::create(
                    [
                        'tariff_id' => $tariff->id,
                        'daily_allowance' => $social['daily_allowance'],
                        'price' => $social['price'],
                        'initial_energy_budget' => $social['initial_energy_budget'],
                        'maximum_stacked_energy' => $social['maximum_stacked_energy'],
                    ]
                );
            }
        }
        if ($tous = request()->input('time_of_usage')) {
            foreach ($tous as $key => $value) {
                TimeOfUsage::create(
                    [
                        'tariff_id' => $tariff->id,
                        'start' => $tous[$key]['start'],
                        'end' => $tous[$key]['end'],
                        'value' => $tous[$key]['value']
                    ]
                );
            }
        }
        if ($components = request()->input('components')) {
            TariffPricingComponentsCalculator::dispatch(
                $tariff,
                $components
            )->allOnConnection('redis')->onQueue(config('services.queues.misc'));
        }
    }
}
