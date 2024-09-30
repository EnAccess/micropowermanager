<?php

namespace Inensus\SteamaMeter\Listeners;

use App\Models\Meter\MeterParameter;
use Faker\Provider\Person;
use Illuminate\Events\Dispatcher;
use Inensus\SteamaMeter\Helpers\ApiHelpers;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Models\SteamaMeter;
use Inensus\SteamaMeter\Models\SteamaTariff;
use Inensus\SteamaMeter\Services\SteamaCustomerService;
use Inensus\SteamaMeter\Services\SteamaMeterService;

class MeterParameterListener
{
    private $apiHelpers;
    private $stmCustomerService;
    private $stmMeterService;
    private $person;
    private $meterParameter;
    private $stmTariff;
    private $stmCustomer;
    private $stmMeter;

    public function __construct(
        ApiHelpers $apiHelpers,
        SteamaMeterService $stmMeterService,
        SteamaCustomerService $stmCustomerService,
        Person $person,
        MeterParameter $meterParameter,
        SteamaTariff $stmTariff,
        SteamaCustomer $stmCustomer,
        SteamaMeter $stmMeter,
    ) {
        $this->apiHelpers = $apiHelpers;
        $this->stmCustomerService = $stmCustomerService;
        $this->stmMeterService = $stmMeterService;
        $this->person = $person;
        $this->meterParameter = $meterParameter;
        $this->stmTariff = $stmTariff;
        $this->stmCustomer = $stmCustomer;
        $this->stmMeter = $stmMeter;
    }

    public function onParameterSaved(int $meterId)
    {
        $meterInfo = $this->meterParameter->newQuery()->with(['meter.manufacturer', 'geo', 'owner.addresses' => static function ($q) {
            $q->where('is_primary', 1);
        }])->whereHas('meter', function ($q) use ($meterId) {
            $q->where('id', $meterId);
        })->first();
        if ($meterInfo->meter->manufacturer->name === 'Steama Meters') {
            $tariffId = $meterInfo->tariff->id;
            $steamaTariff = $this->stmTariff->newQuery()->whereHas('mpmTariff', function ($q) use ($tariffId) {
                $q->where('mpm_tariff_id', $tariffId);
            })->first();
            if (!$steamaTariff) {
                $steamaTariff = $this->stmTariff->newQuery()->with('mpmTariff')->first();
                $meterInfo->tariff_id = $steamaTariff->mpmTariff->id;
                $meterInfo->update();
            }
            if ($meterInfo->owner) {
                if ($meterInfo->owner->is_customer == 1) {
                    $customerId = $meterInfo->owner->id;
                    $steamaCustomer = $this->stmCustomer->newQuery()
                        ->whereHas('mpmPerson', function ($q) use ($customerId) {
                            $q->where('id', $customerId);
                        })->first();
                    if (!$steamaCustomer) {
                        $steamaCustomer = $this->stmCustomerService->createSteamaCustomer($meterInfo);
                    }
                    $steamaMeter = $this->stmMeter->newQuery()->whereHas('mpmMeter', function ($q) use ($meterId) {
                        $q->where('id', $meterId);
                    })->first();
                    if (!$steamaMeter) {
                        $this->stmMeterService->creteSteamaMeter($meterInfo, $steamaCustomer);
                    }
                    $steamaMeter->customer_id = $steamaCustomer->customer_id;
                    $steamaMeter->update();
                }
            }
        }
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            'meterparameter.saved',
            'Inensus\SteamaMeter\Listeners\MeterParameterListener@onParameterSaved'
        );
    }
}
