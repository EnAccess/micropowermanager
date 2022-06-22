<?php

namespace Inensus\SteamaMeter\Observers;

use App\Models\GeographicalInformation;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Models\SteamaMeter;
use Inensus\SteamaMeter\Services\SteamaMeterService;

class GeographicalInformationObserver
{


    private $stmMeterService;
    private $stmMeter;
    private $person;
    private $stmCustomer;
    private $meterParameter;

    public function __construct(
        SteamaMeterService $stmMeterService,
        SteamaMeter $stmMeter,
        Person $person,
        SteamaCustomer $steamaCustomer,
        MeterParameter $meterParameter
    ) {
        $this->stmMeterService = $stmMeterService;
        $this->stmMeter = $stmMeter;
        $this->person = $person;
        $this->stmCustomer = $steamaCustomer;
        $this->meterParameter = $meterParameter;
    }

    public function updated(GeographicalInformation $geographicalInformation)
    {
        if ($geographicalInformation->owner_type === 'meter_parameter') {
            $meterParameter = $this->meterParameter->newQuery()->find($geographicalInformation->owner_id);
            $stmMeter = $this->stmMeter->newQuery()->where('mpm_meter_id', $meterParameter->meter_id)->first();
            if ($stmMeter) {
                $points = explode(',', $geographicalInformation->points);
                $putParams = [
                    'latitude' => floatval($points[0]),
                    'longitude' => floatval($points[1]),
                ];
                $this->stmMeterService->updateSteamaMeterInfo($stmMeter, $putParams);
            }
        }
    }
}
