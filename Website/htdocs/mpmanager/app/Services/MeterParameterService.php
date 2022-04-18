<?php

namespace App\Services;

use App\Models\GeographicalInformation;
use App\Models\Meter\MeterParameter;
use App\Models\Person\Person;
use function Symfony\Component\Translation\t;

class MeterParameterService
{
    public function __construct(
        private SessionService $sessionService,
        private MeterParameter $meterParameter,
    ) {
        $this->sessionService->setModel($meterParameter);
    }

    public function createMeterParameter(
        array $meterParameterData,
        GeographicalInformation $geographicalInformation,
        Person $person
    ): MeterParameter {
        $this->meterParameter->make([
            'meter_id' => $meterParameterData['meter_id'],
            'tariff_id' => $meterParameterData['tariff_id'],
            'connection_type_id' => $meterParameterData['connection_type_id'],
            'connection_group_id' => $meterParameterData['connection_group_id'],
        ]);
        $this->meterParameter->owner()->associate($person);
        $this->meterParameter->geo()->save($geographicalInformation);

        event('accessRatePayment.initialize', $this->meterParameter);
        // changes in_use parameter of the meter
        event('meterparameter.saved', $this->meterParameter->meter_id);
        return $this->meterParameter;
    }
}