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
        $meterParameter = $this->meterParameter->newQuery()->create([
            'owner_type' => 'person',
            'owner_id' => $person->id,
            'meter_id' => $meterParameterData['meter_id'],
            'tariff_id' => $meterParameterData['tariff_id'],
            'connection_type_id' => $meterParameterData['connection_type_id'],
            'connection_group_id' => $meterParameterData['connection_group_id'],
        ]);

        $geographicalInformation->owner()->associate($meterParameter)->save();

        event('accessRatePayment.initialize', $meterParameter);
        // changes in_use parameter of the meter
        event('meterparameter.saved', $meterParameter->meter_id);
        return $meterParameter;
    }
}