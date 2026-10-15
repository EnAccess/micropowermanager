<?php

namespace App\Observers;

use App\Jobs\VerifyDeviceMappingJob;
use App\Models\Device;

class DeviceObserver {
    public function created(Device $device): void {
        $this->verifyManufacturerMapping($device);
    }

    public function updated(Device $device): void {
        if (!$device->wasChanged('device_serial')) {
            return;
        }

        $this->verifyManufacturerMapping($device);
    }

    /**
     * Auto-checks the manufacturer mapping, using the request-scoped companyId
     * set by UserDefaultDatabaseConnectionMiddleware. It is absent outside HTTP
     * requests (seeders, workers), which are skipped.
     */
    private function verifyManufacturerMapping(Device $device): void {
        $companyId = request()->attributes->get('companyId');
        if ($companyId === null) {
            return;
        }

        dispatch(new VerifyDeviceMappingJob($companyId, $device->id));
    }
}
