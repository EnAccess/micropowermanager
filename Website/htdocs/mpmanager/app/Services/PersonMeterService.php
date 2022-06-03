<?php

namespace App\Services;

use App\Models\Meter\Meter;
use App\Models\Person\Person;

class PersonMeterService
{
    public function __construct(
        private Person $person,
        private Meter $meter
    ) {
    parent::__construct([$meter,$person])   ;
    }

    public function getPersonMeters(int $personId)
    {
        return $this->person->newQuery()->with(['meters.tariff', 'meters.meter'])->find($personId);
    }

    public function getPersonMetersGeographicalInformation(int $personId)
    {
        return $this->person->newQuery()->with(['meters.meter', 'meters.geo'])->find($personId);
    }
}
