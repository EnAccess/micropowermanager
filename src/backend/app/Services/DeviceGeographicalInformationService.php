<?php

namespace App\Services;

use App\Models\Device;
use App\Models\GeographicalInformation;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<GeographicalInformation, Device>
 */
class DeviceGeographicalInformationService implements IAssignationService {
    private GeographicalInformation $geographicInformation;
    private Device $device;

    public function setAssigned($assigned): void {
        $this->geographicInformation = $assigned;
    }

    public function setAssignee($assignee): void {
        $this->device = $assignee;
    }

    public function assign(): GeographicalInformation {
        $this->geographicInformation->owner()->associate($this->device);

        return $this->geographicInformation;
    }
}
