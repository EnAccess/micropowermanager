<?php

namespace MPM\SolarHomeSystem;

use App\Models\Device;
use App\Models\SolarHomeSystem;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<Device, SolarHomeSystem>
 */
class SolarHomeSystemDeviceService implements IAssignationService {
    private Device $device;
    protected SolarHomeSystem $shs;

    public function setAssigned($assigned): void {
        $this->device = $assigned;
    }

    public function setAssignee($assignee): void {
        $this->shs = $assignee;
    }

    public function assign(): Device {
        $this->device->device()->associate($this->shs);

        return $this->device;
    }
}
