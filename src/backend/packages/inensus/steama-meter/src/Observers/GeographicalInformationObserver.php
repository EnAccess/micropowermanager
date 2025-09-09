<?php

namespace Inensus\SteamaMeter\Observers;

use App\Models\Address\Address;
use App\Models\Device;
use App\Models\GeographicalInformation;
use Inensus\SteamaMeter\Models\SteamaMeter;
use Inensus\SteamaMeter\Services\SteamaMeterService;

class GeographicalInformationObserver {
    public function __construct(
        private SteamaMeterService $stmMeterService,
        private SteamaMeter $stmMeter,
    ) {}

    public function updated(GeographicalInformation $geographicalInformation) {
        if ($geographicalInformation->owner instanceof Address) {
            $address = $geographicalInformation->owner;
            if ($address->owner instanceof Device) {
                $device = $address->owner;

                $this->updateSteamaMeterGeolocation($device, $geographicalInformation);
            }
        }
    }

    /**
     * Update Steama meter geolocation information.
     *
     * @param Device                  $device
     * @param GeographicalInformation $geographicalInformation
     *
     * @return void
     */
    private function updateSteamaMeterGeolocation(Device $device, GeographicalInformation $geographicalInformation) {
        $meter = $device->device;
        $stmMeter = $this->stmMeter->newQuery()
            ->where('mpm_meter_id', $meter->id)
            ->first();

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
