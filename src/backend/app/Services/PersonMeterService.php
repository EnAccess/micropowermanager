<?php

namespace App\Services;

use App\Models\Person\Person;

class PersonMeterService {
    public function __construct(
        private Person $person,
    ) {}

    public function getPersonMeters(int $personId) {
        return $this->person->newQuery()->with(['devices.device', 'devices.device.tariff'])->find($personId);
    }

    public function getPersonMetersGeographicalInformation(int $personId) {
        return $this->person->newQuery()->with(['devices.device', 'devices.address.geo'])->find($personId);
    }
}
