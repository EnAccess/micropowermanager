<?php

namespace App\Services;

use App\Models\City;
use App\Models\GeographicalInformation;

class CityGeographicalInformationService implements IAssignationService
{
    private City $city;
    private GeographicalInformation $geographicInformation;
    public function setAssigned($assigned)
    {
        $this->geographicInformation = $assigned;
    }

    public function setAssignee($assignee)
    {
        $this->city = $assignee;
    }

    public function assign()
    {
        $this->geographicInformation->owner()->associate($this->city);
        return $this->geographicInformation;
    }
}
