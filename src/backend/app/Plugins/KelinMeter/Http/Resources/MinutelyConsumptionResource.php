<?php

namespace App\Plugins\KelinMeter\Http\Resources;

use App\Plugins\KelinMeter\Models\KelinMeterMinutelyData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KelinMeterMinutelyData
 */
class MinutelyConsumptionResource extends JsonResource {
    /**
     * @return array{
     *     data: array{
     *         type: 'minutely_consumption',
     *         id: int,
     *         attributes: array{
     *             terminalId: int,
     *             measurementPoint: int,
     *             meterAddress: string,
     *             meterName: string,
     *             dateOfData: int,
     *             timeOfData: int,
     *             positiveActiveValue: float,
     *             positiveReactiveValue:float,
     *             invertedActiveValue: float,
     *             invertedReactiveValue: float,
     *             positiveActiveMinute: float,
     *             positiveReactiveMinute: float,
     *             invertedActiveMinute: float,
     *             invertedReactiveMinute: float,
     *             voltageOfPhaseA: float,
     *             voltageOfPhaseB: float,
     *             voltageOfPhaseC: float,
     *             power: float,
     *             powerFactor: float,
     *             reactivePower: float,
     *             currentOfPhaseA: float,
     *             currentOfPhaseB: float,
     *             currentOfPhaseC: float,
     *             temperature1: float,
     *             temperature2: float,
     *             pressure1: float,
     *             pressure2: float,
     *             flowVelocity: float
     *         }
     *     }
     * }
     */
    public function toArray(Request $request): array {
        return [
            'data' => [
                'type' => 'minutely_consumption',
                'id' => $this->id,
                'attributes' => [
                    'terminalId' => $this->id_of_terminal,
                    'measurementPoint' => $this->id_of_measurement_point,
                    'meterAddress' => $this->address_of_meter,
                    'meterName' => $this->name_of_meter,
                    'dateOfData' => $this->date_of_data,
                    'timeOfData' => $this->time_of_data,
                    'positiveActiveValue' => $this->positive_active_value,
                    'positiveReactiveValue' => $this->positive_reactive_value,
                    'invertedActiveValue' => $this->inverted_active_value,
                    'invertedReactiveValue' => $this->inverted_reactive_value,
                    'positiveActiveMinute' => $this->positive_active_minute,
                    'positiveReactiveMinute' => $this->positive_reactive_minute,
                    'invertedActiveMinute' => $this->inverted_active_minute,
                    'invertedReactiveMinute' => $this->inverted_reactive_minute,
                    'voltageOfPhaseA' => $this->voltage_of_phase_a,
                    'voltageOfPhaseB' => $this->voltage_of_phase_b,
                    'voltageOfPhaseC' => $this->voltage_of_phase_c,
                    'power' => $this->power,
                    'powerFactor' => $this->power_factor,
                    'reactivePower' => $this->reactive_power,
                    'currentOfPhaseA' => $this->current_of_phase_a,
                    'currentOfPhaseB' => $this->current_of_phase_b,
                    'currentOfPhaseC' => $this->current_of_phase_c,
                    'temperature1' => $this->temperature_1,
                    'temperature2' => $this->temperature_2,
                    'pressure1' => $this->pressure_1,
                    'pressure2' => $this->pressure_2,
                    'flowVelocity' => $this->flow_velocity,
                ],
            ],
        ];
    }
}
