<?php

namespace App\Observers;

use App\Jobs\VerifyDeviceMappingJob;
use App\Models\Device;

class DeviceObserver {
    /**
     * Auto-checks the manufacturer mapping on device creation, using the
     * request-scoped companyId set by UserDefaultDatabaseConnectionMiddleware.
     * It is absent outside HTTP requests (seeders, workers), which are skipped.
     */
    public function created(Device $device): void {
        $companyId = request()->attributes->get('companyId');
        if ($companyId === null) {
            return;
        }

        dispatch(new VerifyDeviceMappingJob($companyId, $device->id));
    }
}
