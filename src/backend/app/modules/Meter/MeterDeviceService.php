<?php

namespace MPM\Meter;

use App\Models\Device;
use App\Models\Meter\Meter;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<Device, Meter>
 */
class MeterDeviceService implements IAssignationService {
    private Device $device;
    private Meter $meter;

    public function setAssigned($assigned): void {
        $this->device = $assigned;
    }

    public function setAssignee($assignee): void {
        $this->meter = $assignee;
    }

    public function assign(): Device {
        $this->device->device()->associate($this->meter);

        return $this->device;
    }
}
