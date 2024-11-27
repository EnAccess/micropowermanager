<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\GeographicalInformation;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<GeographicalInformation, Address>
 */
class AddressGeographicalInformationService implements IAssignationService {
    private GeographicalInformation $geographicInformation;
    private Address $address;

    public function setAssigned($assigned): void {
        $this->geographicInformation = $assigned;
    }

    public function setAssignee($assignee): void {
        $this->address = $assignee;
    }

    public function assign(): GeographicalInformation {
        $this->geographicInformation->owner()->associate($this->address);

        return $this->geographicInformation;
    }
}
