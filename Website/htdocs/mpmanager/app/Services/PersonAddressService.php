<?php

namespace App\Services;

use App\Models\Person\Person;
use App\Models\Address\Address;

class PersonAddressService implements IAssignationService
{
    private Address $address;
    private Person $person;

    public function setOldPrimaryAddressToNotPrimary(): Person
    {
        $this->person->addresses()->where('is_primary', 1)->update(['is_primary' => 0]);

        return $this->person;
    }


    public function getPersonAddresses($person)
    {
        return $person->addresses()->with('city', 'geo')->orderBy('is_primary', 'DESC')->paginate(5);
    }

    public function setAssigned($address)
    {
        $this->address = $address;
    }

    public function setAssigner($person)
    {
        $this->person = $person;
    }

    public function assign()
    {
        $this->address->owner()->associate($this->person);
        ;

        return $this->address;
    }
}
