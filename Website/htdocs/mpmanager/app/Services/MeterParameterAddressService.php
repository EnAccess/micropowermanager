<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\Meter\MeterParameter;

class MeterParameterAddressService
{
    public function __construct(
        private SessionService $sessionService,
        private Address $address,
        private MeterParameter $meterParameter
    ) {
        $this->sessionService->setModel($address);
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
        $this->sessionService->setModel($address);
    }

    public function setMeterParameter(MeterParameter $meterParameter): void
    {
        $this->meterParameter = $meterParameter;
        $this->sessionService->setModel($meterParameter);
    }
    public function assignAddressToMeterParameter(): Address
    {

        $this->address->owner()->associate($this->meterParameter);;
        return $this->address;
    }
}