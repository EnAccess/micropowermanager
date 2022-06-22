<?php

namespace Inensus\SparkMeter\Listeners;

use App\Models\City;
use App\Models\Meter\MeterParameter;
use App\Models\MiniGrid;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Models\SmSite;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Services\TariffService;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Models\SmTariff;

class MeterParameterListener
{
    private $tariffService;
    private $customerService;
    private $meterParameter;
    private $smTariff;
    private $smCustomer;
    private $miniGrid;
    private $smSite;
    private $city;

    public function __construct(
        TariffService $tariffService,
        CustomerService $customerService,
        MeterParameter $meterParameter,
        SmCustomer $smCustomer,
        SmTariff $smTariff,
        MiniGrid $miniGrid,
        SmSite $smSite,
        City $city
    ) {
        $this->tariffService = $tariffService;
        $this->customerService = $customerService;
        $this->meterParameter = $meterParameter;
        $this->smTariff = $smTariff;
        $this->smCustomer = $smCustomer;
        $this->miniGrid = $miniGrid;
        $this->smSite = $smSite;
        $this->city = $city;
    }

    /**
     * Sets the in_use to true
     * @param int $meter_id
     */
    public function onParameterSaved(int $meter_id)
    {
        Log::debug('Meter Parameter listener Spark Meter Package', []);
        $meterInfo = $this->meterParameter->newQuery()->with([
            'tariff.tou',
            'meter.manufacturer',
            'geo',
            'owner.addresses' =>
                static function ($q) {
                    $q->where('is_primary', 1);
                }
        ])->whereHas('meter', function ($q) use ($meter_id) {
            $q->where('id', $meter_id);
        })->first();
        if ($meterInfo->meter->manufacturer->name === "Spark Meters") {
            $tariffId = $meterInfo->tariff->id;
            $city = $this->city->newQuery()->find($meterInfo->address->city_id);
            $miniGridId = $city->mini_grid_id;

            $smSite = $this->smSite->newQuery()->where('mpm_mini_grid_id', $miniGridId)->first();
            if ($smSite) {
                $smTariff = $this->smTariff->newQuery()->whereHas('mpmTariff', function ($q) use ($tariffId) {
                    $q->where('mpm_tariff_id', $tariffId);
                })->first();
                if (!$smTariff) {
                    $this->tariffService->createSmTariff($meterInfo->tariff, $smSite->site_id);
                }
                if ($meterInfo->owner) {
                    if ($meterInfo->owner->is_customer == 1) {
                        $customerId = $meterInfo->owner->id;
                        $smCustomer = $this->smCustomer->newQuery()->whereHas(
                            'mpmPerson',
                            function ($q) use ($customerId) {
                                $q->where('mpm_customer_id', $customerId);
                            }
                        )->first();
                        if (!$smCustomer) {
                            $this->customerService->createCustomer($meterInfo, $smSite->site_id);
                        }
                    }
                }
            }
        }
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen('meterparameter.saved', 'Inensus\SparkMeter\Listeners\MeterParameterListener@onParameterSaved');
    }
}
