<?php

namespace App\Services;

use App\Models\City;
use App\Models\GeographicalInformation;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<GeographicalInformation, City>
 */
class CityGeographicalInformationService implements IAssignationService {
    private GeographicalInformation $geographicInformation;
    private City $city;

    public function setAssigned($assigned): void {
        $this->geographicInformation = $assigned;
    }

    public function setAssignee($assignee): void {
        $this->city = $assignee;
    }

    public function assign(): GeographicalInformation {
        $this->geographicInformation->owner()->associate($this->city);

        return $this->geographicInformation;
    }
}
