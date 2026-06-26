<?php

namespace App\Plugins\SteamaMeter\Observers;

use App\Models\Device;
use App\Models\GeographicalInformation;
use App\Plugins\SteamaMeter\Models\SteamaMeter;
use App\Plugins\SteamaMeter\Services\SteamaMeterService;

class GeographicalInformationObserver {
    public function __construct(
        private SteamaMeterService $stmMeterService,
        private SteamaMeter $stmMeter,
    ) {}

    public function updated(GeographicalInformation $geographicalInformation): void {
        if ($geographicalInformation->owner instanceof Device) {
            $device = $geographicalInformation->owner;
            $this->updateSteamaMeterGeolocation($device, $geographicalInformation);
        }
    }

    /**
     * Update Steama meter geolocation information.
     */
    private function updateSteamaMeterGeolocation(Device $device, GeographicalInformation $geographicalInformation): void {
        $meter = $device->device;
        $stmMeter = $this->stmMeter->newQuery()
            ->where('mpm_meter_id', $meter->id)
            ->first();

        if ($stmMeter) {
            [$latitude, $longitude] = $geographicalInformation->latitudeLongitude();
            $putParams = [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
            $this->stmMeterService->updateSteamaMeterInfo($stmMeter, $putParams);
        }
    }
}
