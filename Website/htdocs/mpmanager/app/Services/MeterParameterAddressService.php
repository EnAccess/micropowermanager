<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\Meter\MeterParameter;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<MeterParameter, Address>
 */
class MeterParameterAddressService implements IAssignationService
{
    private MeterParameter $meterParameter;
    private Address $address;

    public function setAssignee($meterParameter): void
    {
        $this->meterParameter = $meterParameter;
    }

    public function setAssigned($address): void
    {
        $this->address = $address;
    }

    public function assign(): Address
    {
        $this->address->owner()->associate($this->meterParameter);

        return $this->address;
    }
}
