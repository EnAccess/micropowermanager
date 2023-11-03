<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\Manufacturer;

class ManufacturerAddressService implements IAssignationService
{
    private Address $address;
    private Manufacturer $manufacturer;

    public function setAssigned($address)
    {
        $this->address = $address;
    }

    public function setAssignee($manufacturer)
    {
        $this->manufacturer = $manufacturer;
    }

    public function assign()
    {
        $this->address->owner()->associate($this->manufacturer);

        return $this->address;
    }
}
