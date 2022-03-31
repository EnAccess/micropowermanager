<?php

namespace App\Services;

use App\Models\Person\Person;
use App\Models\Address\Address;

class PersonAddressService
{
    private Person $person;
    private Address $address;

    public function __construct(private SessionService $sessionService)
    {
    }

    public function setAddress(Address $address): void
    {
        $this->address = $address;
        $this->sessionService->setModel($address);
    }

    public function setPerson(Person $person): void
    {
        $this->person = $person;
        $this->sessionService->setModel($person);

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
}