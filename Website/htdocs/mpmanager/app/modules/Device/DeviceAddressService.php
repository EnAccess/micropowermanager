<?php

namespace MPM\Device;

use App\Models\Address\Address;
use App\Models\Device;
use App\Services\IAssignationService;

class DeviceAddressService implements IAssignationService
{

    private Device $device;
    private Address $address;

    public function setAssigned($assigned)
    {
        $this->address = $assigned;
    }

    public function setAssignee($assignee)
    {
        $this->device = $assignee;
    }

    public function assign()
    {
        $this->address->owner()->associate($this->device);
        return $this->address;
    }
}