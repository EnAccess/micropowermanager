<?php

namespace App\Observers;

use App\Jobs\VerifyDeviceMappingJob;
use App\Models\Device;
use App\Services\UserService;

class DeviceObserver {
    /**
     * Auto-checks the manufacturer mapping when a device is added. Guarded on an
     * authenticated user so the check only runs for user-initiated creations —
     * not for seeders, tests, or background factories where no request/company
     * context exists.
     */
    public function created(Device $device): void {
        if (!auth()->hasUser()) {
            return;
        }

        $companyId = resolve(UserService::class)->getCompanyId();
        dispatch(new VerifyDeviceMappingJob($companyId, $device->id));
    }
}
