<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\Device;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<Address, Device>
 */
class DeviceAddressService implements IAssignationService {
    private Address $address;
    private Device $device;

    public function setAssigned($assigned): void {
        $this->address = $assigned;
    }

    public function setAssignee($assignee): void {
        $this->device = $assignee;
    }

    public function assign(): Address {
        $this->address->owner()->associate($this->device);

        return $this->address;
    }

    /**
     * @param array{lat: float|string, lon: float|string} $addressData
     */
    public function updateDeviceAddress(Device $device, array $addressData): Device {
        $points = $addressData['lat'].','.$addressData['lon'];
        $device->geo()->update([
            'points' => $points,
        ]);

        return $device->fresh();
    }
}
