<?php

namespace App\Jobs;

use App\Models\Device;
use App\Services\DeviceControlService;
use Illuminate\Support\Facades\Log;

class VerifyDeviceMappingJob extends AbstractJob {
    public function __construct(int $companyId, private int $deviceId) {
        $this->onConnection('redis');
        $this->onQueue('device');
        $this->afterCommit = true;
        parent::__construct($companyId);
    }

    public function executeJob(): void {
        $device = Device::query()->find($this->deviceId);
        if ($device === null) {
            return;
        }

        try {
            resolve(DeviceControlService::class)->refreshManufacturerMapping($device);
        } catch (\Throwable $throwable) {
            Log::warning('Manufacturer mapping check failed for device '.$this->deviceId, [
                'message' => $throwable->getMessage(),
            ]);
        }
    }
}
