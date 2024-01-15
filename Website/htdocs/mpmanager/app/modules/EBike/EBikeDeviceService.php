<?php

namespace MPM\EBike;

use App\Models\Device;
use App\Models\EBike;
use App\Models\SolarHomeSystem;
use App\Services\IAssignationService;

class EBikeDeviceService implements IAssignationService
{
    private Device $device;
    protected EBike $eBike;
    public function setAssigned($assigned)
    {
        $this->device = $assigned;
    }

    public function setAssignee($assignee)
    {
        $this->eBike = $assignee;
    }

    public function assign()
    {
        $this->device->device()->associate($this->eBike);

        return $this->device;
    }
}
