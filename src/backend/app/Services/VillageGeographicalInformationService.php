<?php

namespace App\Services;

use App\Models\Village;
use App\Models\GeographicalInformation;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<GeographicalInformation, Village>
 */
class VillageGeographicalInformationService implements IAssignationService {
    private GeographicalInformation $geographicInformation;
    private Village $village;

    public function setAssigned($assigned): void {
        $this->geographicInformation = $assigned;
    }

    public function setAssignee($assignee): void {
        $this->village = $assignee;
    }

    public function assign(): GeographicalInformation {
        $this->geographicInformation->owner()->associate($this->village);

        return $this->geographicInformation;
    }
}
