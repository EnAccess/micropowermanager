<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\Meter\MeterParameter;

class MeterParameterAddressService extends BaseService
{
    public function __construct(
        private Address $address,
        private MeterParameter $meterParameter
    ) {
        parent::__construct([$address,$meterParameter]);
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
    }

    public function setMeterParameter(MeterParameter $meterParameter): void
    {
        $this->meterParameter = $meterParameter;
    }
    public function assignAddressToMeterParameter(): Address
    {
        $this->address->owner()->associate($this->meterParameter);;

        return $this->address;
    }
}