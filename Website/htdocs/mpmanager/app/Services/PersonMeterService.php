<?php

namespace App\Services;

use App\Models\Meter\Meter;
use App\Models\Person\Person;

class PersonMeterService
{
    public function __construct(
        private SessionService $sessionService,
        private Person $person,
        private Meter $meter
    ) {
        $this->sessionService->setModel($this->person);
        $this->sessionService->setModel($this->meter);
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