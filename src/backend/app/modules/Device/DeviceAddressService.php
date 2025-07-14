<?php

namespace MPM\Device;

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

    public function getAddressByDevice(Device $device): ?Address {
        $result = Address::query()->with('geo')
            ->where('owner_id', $device->id)
            ->where('owner_type', 'device')
            ->first();

        return $result;
    }

    /**
     * @return Address
     */
    public function updateDeviceAddress(Address $deviceAddress, array $addressData): Address {
        $points = $addressData['lat'].','.$addressData['lon'];
        $deviceAddress->geo()->update([
            'points' => $points,
        ]);
        $deviceAddress->save();

        return $deviceAddress->fresh();
    }
}
