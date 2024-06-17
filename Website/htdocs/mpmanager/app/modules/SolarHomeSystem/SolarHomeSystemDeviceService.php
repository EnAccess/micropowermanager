<?php

namespace MPM\SolarHomeSystem;

use App\Models\Device;
use App\Models\SolarHomeSystem;
use App\Services\IAssignationService;

class SolarHomeSystemDeviceService implements IAssignationService
{
    private Device $device;
    protected SolarHomeSystem $shs;

    public function setAssigned($assigned)
    {
        $this->device = $assigned;
    }

    public function setAssignee($assignee)
    {
        $this->shs = $assignee;
    }

    public function assign()
    {
        $this->device->device()->associate($this->shs);

        return $this->device;
    }
}
