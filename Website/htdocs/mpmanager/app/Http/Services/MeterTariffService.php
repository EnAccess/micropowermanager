<?php

namespace App\Http\Services;

use App\Http\Requests\TariffCreateRequest;
use App\Jobs\TariffPricingComponentsCalculator;
use App\Models\AccessRate\AccessRate;
use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;
use App\Models\SocialTariff;
use App\Models\TariffPricingComponent;
use App\Models\TimeOfUsage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MeterTariffService
{
    /**
     * @param MeterTariff $tariff
     * @param TariffCreateRequest $request
     * @return Model|Builder|Builder[]|Collection|null
     */
    public function update(MeterTariff $tariff, TariffCreateRequest $request)
    {
        if ($accessRate = request()->input('access_rate')) {
            if ($accessRate['id']) {
                $updatedAccessRate = AccessRate::find($accessRate['id']);
                $updatedAccessRate->amount = $accessRate['access_rate_amount'];
                $updatedAccessRate->period = $accessRate['access_rate_period'];
                $updatedAccessRate->update();
            } else {
                AccessRate::create([
                        'tariff_id' => $tariff->id,
                        'amount' => $accessRate['access_rate_amount'],
                        'period' => $accessRate['access_rate_period'],
                    ]);
            }
        } else {
            AccessRate::where('tariff_id', $tariff->id)->delete();
        }
        if ($social = request()->input('social_tariff')) {
            if ($social['id']) {
                $updatedSocialTariff = SocialTariff::find($social['id']);
                $updatedSocialTariff->daily_allowance = $social['daily_allowance'];
                $updatedSocialTariff->price = $social['price'];
                $updatedSocialTariff->initial_energy_budget = $social['initial_energy_budget'];
                $updatedSocialTariff->maximum_stacked_energy = $social['maximum_stacked_energy'];
                $updatedSocialTariff->update();
            } else {
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
        } else {
            SocialTariff::where('tariff_id', $tariff->id)->delete();
        }

        $pricingComponents = TariffPricingComponent::where('owner_type', 'meter_tariff')->where(
            'owner_id',
            $tariff->id
        )->get();
        if ($pricingComponents) {
            foreach ($pricingComponents as $key => $pricing) {
                TariffPricingComponent::where('id', $pricing->id)->delete();
            }
        }
        if ($components = request()->input('components')) {
            TariffPricingComponentsCalculator::dispatch(
                $tariff,
                $components
            )->allOnConnection('redis')->onQueue(config('services.queues.misc'));
        }

        if ($tous = request()->input('time_of_usage')) {
            foreach ($tous as $key => $value) {
                $tou = TimeOfUsage::find($tous[$key]['id']);
                if ($tou) {
                    $tou->start = $tous[$key]['start'];
                    $tou->end = $tous[$key]['end'];
                    $tou->value = $tous[$key]['value'];
                    $tou->update();
                } else {
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
        }
        $tariff->update(
            [
            'name' => $request->input('name'),
            'factor' => $request->input('factor'),
            'currency' => $request->input('currency'),
            'price' => $request->input('price'),
            'total_price' => $request->input('price'),
             'updated_at' => date('Y-m-d h:i:s')
            ]
        );

        return $meterTariff = MeterTariff::with(
            [
            'accessRate',
            'pricingComponent',
            'socialTariff',
            'tou'
            ]
        )->find($tariff->id);
    }

    public function meterTariffUsageCount($tariffId): array
    {

        return DB::select(
            DB::raw(
                "select COUNT(meters.id) as count from meter_tariffs
               inner join meter_parameters on meter_tariffs.id=meter_parameters.tariff_id
               inner join meters on meter_parameters.meter_id=meters.id
               where meters.in_use =1 and meter_tariffs.id=$tariffId"
            )
        );
    }

    /**
     * @return Builder[]|Collection
     *
     * @psalm-return \Illuminate\Database\Eloquent\Collection|array<array-key, \Illuminate\Database\Eloquent\Builder>
     */
    public function changeMetersTariff($currentId, int $changeId)
    {
        $meterParameters = MeterParameter::query()->where('tariff_id', $currentId)->get();
        foreach ($meterParameters as $key => $value) {
            $value->tariff_id = $changeId;
            $value->update();
        }
        return $meterParameters;
    }
}
