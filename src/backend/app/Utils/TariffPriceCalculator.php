<?php

namespace App\Utils;

use App\Http\Requests\TariffCreateRequest;
use App\Models\Meter\MeterTariff;
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
        MeterTariff $meterTariff,
        TariffCreateRequest $request,
    ): void {
        $accessRate = $request->input('access_rate');
        $socialTariff = $request->input('social_tariff');
        $timeOfUsage = $request->input('time_of_usage');
        $additionalComponents = $request->input('components');

        $meterTariff->total_price = $meterTariff->price;
        $meterTariff->save();

        $this->setAccessRate($accessRate, $meterTariff);
        $this->setSocialTariff($socialTariff, $meterTariff);
        $this->setTimeOfUsages($timeOfUsage, $meterTariff);
        $this->setAdditionalComponents($additionalComponents, $meterTariff);
    }

    private function setAccessRate(mixed $accessRate, MeterTariff $meterTariff): void {
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
                    'tariff_id' => $meterTariff->id,
                    'amount' => $accessRate['access_rate_amount'],
                    'period' => $accessRate['access_rate_period'],
                ];

                $this->accessRateService->create($accessRateData);
            }
        } else {
            $this->accessRateService->deleteByTariffId($meterTariff->id);
        }
    }

    private function setSocialTariff(mixed $socialTariff, MeterTariff $meterTariff): void {
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
                    'tariff_id' => $meterTariff->id,
                    'daily_allowance' => $socialTariff['daily_allowance'],
                    'price' => $socialTariff['price'],
                    'initial_energy_budget' => $socialTariff['initial_energy_budget'],
                    'maximum_stacked_energy' => $socialTariff['maximum_stacked_energy'],
                ];

                $this->socialTariffService->create($socialTariffData);
            }
        } else {
            $this->socialTariffService->deleteByTariffId($meterTariff->id);
        }
    }

    private function setTimeOfUsages(mixed $timeOfUsage, MeterTariff $meterTariff): void {
        if ($timeOfUsage) {
            foreach ($timeOfUsage as $key => $value) {
                $tou = isset($timeOfUsage[$key]['id']) ? $this->timeOfUsageService->getById($timeOfUsage[$key]['id']) :
                    null;

                if ($tou) {
                    $touData = [
                        'start' => $timeOfUsage[$key]['start'],
                        'end' => $timeOfUsage[$key]['end'],
                        'value' => $timeOfUsage[$key]['value'],
                    ];
                    $this->timeOfUsageService->update($tou, $touData);
                } else {
                    $touData = [
                        'tariff_id' => $meterTariff->id,
                        'start' => $timeOfUsage[$key]['start'],
                        'end' => $timeOfUsage[$key]['end'],
                        'value' => $timeOfUsage[$key]['value'],
                    ];
                    $this->timeOfUsageService->create($touData);
                }
            }
        }
    }

    private function setAdditionalComponents(mixed $additionalComponents, MeterTariff $meterTariff): void {
        $this->tariffPricingComponentService->deleteByTariffId($meterTariff->id);

        if ($additionalComponents) {
            $totalPrice = $meterTariff->price;

            foreach ($additionalComponents as $component) {
                $totalPrice += $component['price'];
                $tariffPricingComponentData = [
                    'name' => $component['name'],
                    'price' => $component['price'],
                ];

                $tariffPricingComponent = $this->tariffPricingComponentService->make($tariffPricingComponentData);
                $meterTariff->pricingComponent()->save($tariffPricingComponent);
            }
            $meterTariff->update(['total_price' => $totalPrice]);
        }
    }
}
