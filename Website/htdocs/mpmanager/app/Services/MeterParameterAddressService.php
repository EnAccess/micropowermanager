<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\Meter\MeterParameter;

class MeterParameterAddressService implements IAssignationService
{
    private Address $address;
    private MeterParameter $meterParameter;

    public function setAssignee($meterParameter)
    {
        $this->meterParameter = $meterParameter;
    }

    public function setAssigned($address)
    {
        $this->address = $address;
    }

    public function assign(): Address
    {
        $this->address->owner()->associate($this->meterParameter);

        return $this->address;
    }
}
