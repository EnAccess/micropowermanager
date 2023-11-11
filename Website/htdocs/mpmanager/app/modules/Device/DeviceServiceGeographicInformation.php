<?php

namespace MPM\Device;

use App\Models\Device;
use App\Models\GeographicalInformation;
use App\Services\IAssignationService;

class DeviceServiceGeographicInformation implements IAssignationService
{
    private Device $device;
    private GeographicalInformation $geographicInformation;
    public function setAssigned($assigned)
    {
      $this->geographicInformation = $assigned;
    }

    public function setAssignee($assignee)
    {
        $this->device = $assignee;
    }

    public function assign()
    {
        $this->geographicInformation->owner()->associate($this->device);
        return $this->geographicInformation;
    }
}