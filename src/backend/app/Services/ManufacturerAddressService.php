<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\Manufacturer;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<Address, Manufacturer>
 */
class ManufacturerAddressService implements IAssignationService {
    private Address $address;
    private Manufacturer $manufacturer;

    public function setAssigned($address): void {
        $this->address = $address;
    }

    public function setAssignee($manufacturer): void {
        $this->manufacturer = $manufacturer;
    }

    public function assign(): Address {
        $this->address->owner()->associate($this->manufacturer);

        return $this->address;
    }
}
