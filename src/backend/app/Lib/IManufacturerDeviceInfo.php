<?php

namespace App\Lib;

use App\Models\Device;

/**
 * Opt-in capability for manufacturers whose API can report whether a device
 * unit is known/mapped on their side.
 *
 * Only manufacturers that expose a device-lookup endpoint implement this;
 * callers must check `instanceof` before use so that manufacturers without
 * such an endpoint are reported as "unsupported" rather than failing.
 */
interface IManufacturerDeviceInfo {
    /**
     * @return array{mapped: bool, device: array<string, mixed>|null}
     */
    public function getDeviceInfo(Device $device): array;
}
