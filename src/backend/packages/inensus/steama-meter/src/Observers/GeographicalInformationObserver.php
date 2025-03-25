<?php

namespace Inensus\SteamaMeter\Observers;

use App\Models\GeographicalInformation;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Models\SteamaMeter;
use Inensus\SteamaMeter\Services\SteamaMeterService;

class GeographicalInformationObserver {
    private $stmMeterService;
    private $stmMeter;
    private $person;
    private $stmCustomer;
    private $meter;

    public function __construct(
        SteamaMeterService $stmMeterService,
        SteamaMeter $stmMeter,
        Person $person,
        SteamaCustomer $steamaCustomer,
        Meter $meter,
    ) {
        $this->stmMeterService = $stmMeterService;
        $this->stmMeter = $stmMeter;
        $this->person = $person;
        $this->stmCustomer = $steamaCustomer;
        $this->meter = $meter;
    }

    public function updated(GeographicalInformation $geographicalInformation) {
        if ($geographicalInformation->owner_type === 'meter_parameter') {
            $meter = $this->meter->newQuery()->find($geographicalInformation->owner_id);
            $stmMeter = $this->stmMeter->newQuery()->where('mpm_meter_id', $meter->id)->first();
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
