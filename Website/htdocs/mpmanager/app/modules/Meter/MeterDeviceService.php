<?php

namespace MPM\Meter;

use App\Models\Device;
use App\Models\Meter\Meter;
use App\Services\IAssignationService;

class MeterDeviceService implements IAssignationService
{
    private Device $device;
    private Meter $meter;

    public function setAssigned($assigned)
    {
        $this->device = $assigned;
    }

    public function setAssignee($assignee)
    {
        $this->meter = $assignee;
    }

    public function assign()
    {
        $this->device->device()->associate($this->meter);

        return $this->device;
    }

}