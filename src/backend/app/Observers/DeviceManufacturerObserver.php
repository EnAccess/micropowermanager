<?php

namespace App\Observers;

use App\Models\Device;
use App\Models\EBike;
use App\Models\Meter\Meter;
use App\Models\SolarHomeSystem;
use App\Services\DeviceControlService;

class DeviceManufacturerObserver {
    public function updated(Meter|SolarHomeSystem|EBike $deviceModel): void {
        if (!$deviceModel->wasChanged('manufacturer_id')) {
            return;
        }

        $device = $deviceModel->device;

        if (!$device instanceof Device) {
            return;
        }

        resolve(DeviceControlService::class)->resetInvalidVendingPlugin($device);
    }
}
