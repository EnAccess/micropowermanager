<?php

namespace App\Services;

use App\Models\Person\Person;
use App\Models\Address\Address;

class PersonAddressService extends BaseService
{


    public function __construct(private Address $address,private Person $person)
    {
        parent::__construct([$address,$person]);
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;

    }

    public function setPerson(Person $person): void
    {
        $this->person = $person;
    }


    public function setOldPrimaryAddressToNotPrimary(): Person
    {
        $this->person->addresses()->where('is_primary', 1)->update(['is_primary' => 0]);
        return $this->person;
    }

    public function assignAddressToPerson(): Address
    {
        $this->address->owner()->associate($this->person);;
        return $this->address;
    }

    public function getPersonAddresses($person)
    {
       return $person->addresses()->with('city', 'geo')->orderBy('is_primary', 'DESC')->paginate(5);
    }
}