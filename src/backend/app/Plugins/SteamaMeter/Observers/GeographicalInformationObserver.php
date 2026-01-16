<?php

namespace App\Plugins\SteamaMeter\Observers;

use App\Models\Address\Address;
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
     */
    private function updateSteamaMeterGeolocation(Device $device, GeographicalInformation $geographicalInformation): void {
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
