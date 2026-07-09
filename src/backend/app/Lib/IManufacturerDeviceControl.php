<?php

namespace App\Lib;

use App\Models\Device;

/**
 * Opt-in capability for manufacturers that expose an out-of-band device
 * management API. It lets MPM read device status and control the unit without
 * forcing every manufacturer to support it — callers resolve it by api_name
 * and treat a non-implementer as "unsupported".
 */
interface IManufacturerDeviceControl {
    /**
     * @return array{mapped: bool, device: array<string, mixed>|null}
     */
    public function getDeviceInfo(Device $device): array;
}
