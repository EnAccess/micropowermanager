<?php

namespace MPM\EBike;

use App\Models\Device;
use App\Models\EBike;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<Device, EBike>
 */
class EBikeDeviceService implements IAssignationService {
    private Device $device;
    protected EBike $eBike;

    public function setAssigned($assigned): void {
        $this->device = $assigned;
    }

    public function setAssignee($assignee): void {
        $this->eBike = $assignee;
    }

    public function assign(): Device {
        $this->device->device()->associate($this->eBike);

        return $this->device;
    }
}
