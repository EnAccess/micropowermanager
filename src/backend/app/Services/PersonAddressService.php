<?php

namespace App\Services;

use App\Models\Address\Address;
use App\Models\Person\Person;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<Address, Person>
 */
class PersonAddressService implements IAssignationService {
    private Address $address;
    private Person $person;

    public function setOldPrimaryAddressToNotPrimary(): Person {
        $this->person->addresses()->where('is_primary', 1)->update(['is_primary' => 0]);

        return $this->person;
    }

    public function getPersonAddresses($person): mixed {
        return $person->addresses()->with('city', 'geo')->orderBy('is_primary', 'DESC')->paginate(5);
    }

    public function setAssigned($address): void {
        $this->address = $address;
    }

    public function setAssignee($person): void {
        $this->person = $person;
    }

    public function assign(): Address {
        $this->address->owner()->associate($this->person);

        return $this->address;
    }
}
