<?php

namespace App\Utils;

use App\Http\Requests\TariffCreateRequest;
use App\Models\Tariff;
use App\Models\TimeOfUsage;
use App\Services\AccessRateService;
use App\Services\SocialTariffService;
use App\Services\TariffPricingComponentService;
use App\Services\TimeOfUsageService;

class TariffPriceCalculator {
    public function __construct(
        private AccessRateService $accessRateService,
        private SocialTariffService $socialTariffService,
        private TimeOfUsageService $timeOfUsageService,
        private TariffPricingComponentService $tariffPricingComponentService,
    ) {}

    public function calculateTotalPrice(
        Tariff $tariff,
        TariffCreateRequest $request,
    ): void {
        $accessRate = $request->input('access_rate');
        $socialTariff = $request->input('social_tariff');
        $timeOfUsage = $request->input('time_of_usage');
        $additionalComponents = $request->input('components');

        $this->setAccessRate($accessRate, $tariff);
        $this->setSocialTariff($socialTariff, $tariff);
        $this->setTimeOfUsages($timeOfUsage, $tariff);
        $this->setAdditionalComponents($additionalComponents, $tariff);
    }

    private function setAccessRate(mixed $accessRate, Tariff $tariff): void {
        if ($accessRate) {
            if (isset($accessRate['id'])) {
                $updatedAccessRate = $this->accessRateService->getById($accessRate['id']);
                $accessRateData = [
                    'amount' => $accessRate['access_rate_amount'],
                    'period' => $accessRate['access_rate_period'],
                ];

                $this->accessRateService->update($updatedAccessRate, $accessRateData);
            } else {
                $accessRateData = [
                    'tariff_id' => $tariff->id,
                    'amount' => $accessRate['access_rate_amount'],
                    'period' => $accessRate['access_rate_period'],
                ];

                $this->accessRateService->create($accessRateData);
            }
        } else {
            $this->accessRateService->deleteByTariffId($tariff->id);
        }
    }

    private function setSocialTariff(mixed $socialTariff, Tariff $tariff): void {
        if ($socialTariff) {
            if (isset($socialTariff['id'])) {
                $updatedSocialTariff = $this->socialTariffService->getById($socialTariff['id']);
                $socialTariffData = [
                    'daily_allowance' => $socialTariff['daily_allowance'],
                    'price' => $socialTariff['price'],
                    'initial_energy_budget' => $socialTariff['initial_energy_budget'],
                    'maximum_stacked_energy' => $socialTariff['maximum_stacked_energy'],
                ];

                $this->socialTariffService->update($updatedSocialTariff, $socialTariffData);
            } else {
                $socialTariffData = [
                    'tariff_id' => $tariff->id,
                    'daily_allowance' => $socialTariff['daily_allowance'],
                    'price' => $socialTariff['price'],
                    'initial_energy_budget' => $socialTariff['initial_energy_budget'],
                    'maximum_stacked_energy' => $socialTariff['maximum_stacked_energy'],
                ];

                $this->socialTariffService->create($socialTariffData);
            }
        } else {
            $this->socialTariffService->deleteByTariffId($tariff->id);
        }
    }

    private function setTimeOfUsages(mixed $timeOfUsage, Tariff $tariff): void {
        if ($timeOfUsage) {
            foreach ($timeOfUsage as $key => $value) {
                $tou = isset($timeOfUsage[$key]['id']) ? $this->timeOfUsageService->getById($timeOfUsage[$key]['id']) :
                    null;

                if ($tou instanceof TimeOfUsage) {
                    $touData = [
                        'start' => $timeOfUsage[$key]['start'],
                        'end' => $timeOfUsage[$key]['end'],
                        'value' => $timeOfUsage[$key]['value'],
                    ];
                    $this->timeOfUsageService->update($tou, $touData);
                } else {
                    $touData = [
                        'tariff_id' => $tariff->id,
                        'start' => $timeOfUsage[$key]['start'],
                        'end' => $timeOfUsage[$key]['end'],
                        'value' => $timeOfUsage[$key]['value'],
                    ];
                    $this->timeOfUsageService->create($touData);
                }
            }
        }
    }

    private function setAdditionalComponents(mixed $additionalComponents, Tariff $tariff): void {
        $this->tariffPricingComponentService->deleteByTariffId($tariff->id);

        if ($additionalComponents) {
            foreach ($additionalComponents as $component) {
                $tariffPricingComponentData = [
                    'name' => $component['name'],
                    'price' => $component['price'],
                ];

                $tariffPricingComponent = $this->tariffPricingComponentService->make($tariffPricingComponentData);
                $tariff->pricingComponent()->save($tariffPricingComponent);
            }
        }
    }
}
