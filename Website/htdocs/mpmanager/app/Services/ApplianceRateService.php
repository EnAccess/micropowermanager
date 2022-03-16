<?php

namespace App\Services;

use App\Models\AssetRate;
use App\Models\MainSettings;
use Carbon\Carbon;

class ApplianceRateService
{
    private $applianceRate;
    private $mainSettings;

    public function __construct(AssetRate $applianceRate, MainSettings $mainSettings)
    {
        $this->applianceRate = $applianceRate;
        $this->mainSettings = $mainSettings;
    }

    public function getCurrencyFromMainSettings()
    {
        $mainSettings = $this->mainSettings->newQuery()->first();
        return $mainSettings === null ? '€' : $mainSettings->currency;
    }

    public function updateApplianceRateCost($applianceRate, $creatorId, $cost, $newCost)
    {
        $currency = $this->getCurrencyFromMainSettings();
        event(
            'new.log',
            [
                'logData' => [
                    'user_id' => $creatorId,
                    'affected' => $applianceRate->assetPerson,
                    'action' => 'Appliance rate ' . date(
                        'd-m-Y',
                        strtotime($applianceRate->due_date)
                    ) . ' cost updated. From '
                        . $cost . ' ' . $currency . ' to ' . $newCost . ' ' . $currency
                ]
            ]
        );
        $applianceRate->rate_cost = $newCost;
        $applianceRate->remaining = $newCost;
        $applianceRate->update();
        $applianceRate->save();
        return $applianceRate->fresh();
    }
    public function deleteUpdatedApplianceRateIfCostZero($applianceRate, $creatorId, $cost, $newCost)
    {
        $currency = $this->getCurrencyFromMainSettings();
        $appliancePerson = $applianceRate->assetPerson;
        $applianceRate->delete();
        event(
            'new.log',
            [
                'logData' => [
                    'user_id' => $creatorId,
                    'affected' => $appliancePerson,
                    'action' => 'Appliance rate ' . date(
                        'd-m-Y',
                        strtotime($applianceRate->due_date)
                    ) . ' deleted. From '
                        . $cost . ' ' . $currency . ' to ' . $newCost . ' ' . $currency
                ]
            ]
        );
    }

    public function createApplianceRatesFromAssetPerson($assetPerson)
    {
        $base_time = $assetPerson->first_payment_date ?? date('Y-m-d');

        if ($assetPerson->down_payment > 0) {
            $this->applianceRate::query()->create(
                [
                    'asset_person_id' => $assetPerson->id,
                    'rate_cost' => $assetPerson->down_payment,
                    'remaining' => 0,
                    'due_date' => Carbon::parse(date('Y-m-d'))->toIso8601ZuluString(),
                    'remind' => 0
                ]
            );
            $assetPerson->total_cost -= $assetPerson->down_payment;
        }

        foreach (range(1, $assetPerson->rate_count) as $rate) {
            if ($assetPerson->rate_count === 0) {
                $rate_cost = 0;
            } elseif ((int)$rate === (int)$assetPerson->rate_count) {
                //last rate
                $rate_cost = $assetPerson->total_cost
                    - (($rate - 1) * floor($assetPerson->total_cost / $assetPerson->rate_count));
            } else {
                $rate_cost = floor($assetPerson->total_cost / $assetPerson->rate_count);
            }
            $rate_date = date('Y-m-d', strtotime('+' . $rate . ' month', strtotime($base_time)));
            $this->applianceRate::query()->create(
                [
                    'asset_person_id' => $assetPerson->id,
                    'rate_cost' => $rate_cost,
                    'remaining' => $rate_cost,
                    'due_date' => $rate_date,
                    'remind' => 0
                ]
            );
        }
    }
}
