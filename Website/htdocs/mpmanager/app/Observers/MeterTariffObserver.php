<?php

namespace App\Observers;

use App\Http\Requests\TariffCreateRequest;
use App\Jobs\TariffPricingComponentsCalculator;
use App\Models\Meter\MeterTariff;
use App\Services\AccessRateService;
use App\Services\SocialTariffService;
use App\Services\TariffPricingComponentService;
use App\Services\TimeOfUsageService;
use Illuminate\Http\Request;

class MeterTariffObserver
{

    public function __construct(
        private AccessRateService $accessRateService,
        private SocialTariffService $socialTariffService,
        private TimeOfUsageService $timeOfUsageService,
        private TariffPricingComponentService $tariffPricingComponentService
    ) {
    }

    public function created(MeterTariff $tariff): void
    {
        $accessRate = request()->input('access_rate');

        if ($accessRate) {
            $accessRateData = [
                'tariff_id' => $tariff->id,
                'amount' => $accessRate['access_rate_amount'],
                'period' => $accessRate['access_rate_period']
            ];
            $this->accessRateService->create($accessRateData);
        }

        $social = request()->input('social_tariff');

        if ($social) {
            // create social tariff for the given tariff

            if (
                $social['daily_allowance'] !== null && $social['price'] && $social['initial_energy_budget'] &&
                $social['maximum_stacked_energy']
            ) {
                $socialTariffData = [
                    'tariff_id' => $tariff->id,
                    'daily_allowance' => $social['daily_allowance'],
                    'price' => $social['price'],
                    'initial_energy_budget' => $social['initial_energy_budget'],
                    'maximum_stacked_energy' => $social['maximum_stacked_energy'],
                ];
                $this->socialTariffService->create($socialTariffData);
            }
        }

        $tous = request()->input('time_of_usage');

        if ($tous) {
            foreach ($tous as $key => $value) {
                $timeOfUsageData = [
                    'tariff_id' => $tariff->id,
                    'start' => $tous[$key]['start'],
                    'end' => $tous[$key]['end'],
                    'value' => $tous[$key]['value']
                    ];
                $this->timeOfUsageService->create($timeOfUsageData);
            }
        }

        $components = request()->input('components');

        if ($components) {
            TariffPricingComponentsCalculator::dispatch(
                $tariff,
                $components,
                $this->tariffPricingComponentService
            )->allOnConnection('redis')->onQueue(config('services.queues.misc'));
        }
    }

    public function updated(MeterTariff $tariff)
    {
        $accessRate = request()->input('access_rate');

        if ($accessRate) {

            if (isset($accessRate['id'])) {

                $updatedAccessRate = $this->accessRateService->getById($accessRate['id']);
                $accessRateData =[
                    'amount' => $accessRate['access_rate_amount'],
                    'period' => $accessRate['access_rate_period']
                ];

                $this->accessRateService->update($updatedAccessRate, $accessRateData);
            } else {
                $accessRateData =[
                    'tariff_id' => $tariff->id,
                    'amount' => $accessRate['access_rate_amount'],
                    'period' => $accessRate['access_rate_period']
                ];

                $this->accessRateService->create($accessRateData);
            }
        } else {
            $this->accessRateService->deleteByTariffId($tariff->id);
        }

        $social = request()->input('social_tariff');

        if ($social) {

            if (isset($social['id'])) {

                $updatedSocialTariff = $this->socialTariffService->getById($social['id']);
                $socialTariffData =[
                    'daily_allowance' => $social['daily_allowance'],
                    'price' => $social['price'],
                    'initial_energy_budget' => $social['initial_energy_budget'],
                    'maximum_stacked_energy' => $social['maximum_stacked_energy'],
                ];

                $this->socialTariffService->update($updatedSocialTariff, $socialTariffData);
            } else {
                $socialTariffData =[
                    'tariff_id' => $tariff->id,
                    'daily_allowance' => $social['daily_allowance'],
                    'price' => $social['price'],
                    'initial_energy_budget' => $social['initial_energy_budget'],
                    'maximum_stacked_energy' => $social['maximum_stacked_energy'],
                ];

                $this->socialTariffService->create($socialTariffData);
            }
        } else {
            $this->socialTariffService->deleteByTariffId($tariff->id);
        }

        $this->tariffPricingComponentService->deleteByTariffId($tariff->id);
        $components = request()->input('components');

        if ($components) {
            TariffPricingComponentsCalculator::dispatch(
                $tariff,
                $components,
                $this->tariffPricingComponentService
            )->allOnConnection('redis')->onQueue(config('services.queues.misc'));
        }

        $tous = request()->input('time_of_usage');

        if ($tous) {
            foreach ($tous as $key => $value) {

                $tou = isset($tous[$key]['id'])? $this->timeOfUsageService->getById($tous[$key]['id']): null;

                if ($tou) {
                    $touData =[
                        'start' => $tous[$key]['start'],
                        'end' => $tous[$key]['end'],
                        'value' => $tous[$key]['value']
                    ];
                $this->timeOfUsageService->update($tou, $touData);
                } else {
                    $touData =[
                        'tariff_id' => $tariff->id,
                        'start' => $tous[$key]['start'],
                        'end' => $tous[$key]['end'],
                        'value' => $tous[$key]['value']
                    ];
                    $this->timeOfUsageService->create($touData);
                }
            }
        }
    }
}
