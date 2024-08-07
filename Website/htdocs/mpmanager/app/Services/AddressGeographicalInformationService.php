<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use App\Services\Interfaces\IAssignationService;

class AddressGeographicalInformationService implements IAssignationService
{
    private Address $address;
    private GeographicalInformation $geographicInformation;

    public function setAssigned($assigned)
    {
        $this->geographicInformation = $assigned;
    }

    public function setAssignee($assignee)
    {
        $this->address = $assignee;
    }

    public function assign()
    {
        $this->geographicInformation->owner()->associate($this->address);

        return $this->geographicInformation;
    }
}
