<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\Manufacturer;


class ManufacturerAddressService extends BaseService
{

    public function __construct(private Address $address,private Manufacturer $manufacturer)
    {
        parent::__construct([$address,$manufacturer]);
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;

    }

    public function setManufacturer(Manufacturer $manufacturer): void
    {
        $this->manufacturer = $manufacturer;
    }

    public function assignAddressToPerson(): Address
    {
        $this->address->owner()->associate($this->manufacturer);

        return $this->address;
    }
}