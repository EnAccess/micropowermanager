<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\BaseModel;
use App\Models\Meter\MeterParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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
        ;

        return $this->address;
    }
}
