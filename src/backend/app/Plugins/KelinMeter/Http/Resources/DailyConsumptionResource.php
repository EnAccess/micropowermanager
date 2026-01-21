<?php

namespace App\Plugins\KelinMeter\Http\Resources;

use App\Plugins\KelinMeter\Models\KelinMeterDailyData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin KelinMeterDailyData
 */
class DailyConsumptionResource extends JsonResource {
    /**
     * @return array{
     *     data: array{
     *         type: 'daily_consumption',
     *         id: int,
     *         attributes: array{
     *             terminalId: int,
     *             measurementPoint: int,
     *             meterAddress: string,
     *             meterName: string,
     *             dateOfData: int,
     *             totalValueOfPositiveActivePowerCumulativeFlowIndication: float,
     *             totalValueOfPositiveActivePeakPower: float,
     *             totalValueOfPositiveActiveFlatPower: float,
     *             totalValueOfPositiveActiveValleyPower: float,
     *             totalValueOfPositiveActiveSpikePower: float,
     *             totalValueOfPositiveReactivePowerCumulativeFlowIndication: float,
     *             totalValueOfPositiveReactivePeakPower: float,
     *             totalValueOfPositiveReactiveFlatPower: float,
     *             totalValueOfPositiveReactiveValleyPower: float,
     *             totalValueOfPositiveReactiveSpikePower: float,
     *             totalValueOfRevertedActivePowerCumulativeFlowIndication: float,
     *             totalValueOfRevertedReactivePowerCumulativeFlowIndication: float,
     *             positiveActiveTotalDailyPower: float,
     *             positiveActiveDailyPowerInPeak: float,
     *             positiveActiveDailyPowerInFlat: float,
     *             positiveActiveDailyPowerInValley: float,
     *             positiveActiveDailyPowerInSpike: float,
     *             positiveReactiveTotalDailyPower: float,
     *             revertedActiveTotalDailyPower: float,
     *             revertedReactiveTotalDailyPower: float
     *         }
     *     }
     * }
     */
    public function toArray(Request $request) {
        return [
            'data' => [
                'type' => 'daily_consumption',
                'id' => $this->id,
                'attributes' => [
                    'terminalId' => $this->id_of_terminal,
                    'measurementPoint' => $this->id_of_measurement_point,
                    'meterAddress' => $this->address_of_meter,
                    'meterName' => $this->name_of_meter,
                    'dateOfData' => $this->date_of_data,
                    'totalValueOfPositiveActivePowerCumulativeFlowIndication' => $this->total_positive_active_power_cumulative_flow_indication,
                    'totalValueOfPositiveActivePeakPower' => $this->total_positive_active_peak_power,
                    'totalValueOfPositiveActiveFlatPower' => $this->total_positive_active_flat_power,
                    'totalValueOfPositiveActiveValleyPower' => $this->total_positive_active_valley_power,
                    'totalValueOfPositiveActiveSpikePower' => $this->total_positive_active_spike_power,
                    'totalValueOfPositiveReactivePowerCumulativeFlowIndication' => $this->total_positive_reactive_power_cumulative_flow_indication,
                    'totalValueOfPositiveReactivePeakPower' => $this->total_positive_reactive_peak_power,
                    'totalValueOfPositiveReactiveFlatPower' => $this->total_positive_reactive_flat_power,
                    'totalValueOfPositiveReactiveValleyPower' => $this->total_positive_reactive_valley_power,
                    'totalValueOfPositiveReactiveSpikePower' => $this->total_positive_reactive_spike_power,
                    'totalValueOfRevertedActivePowerCumulativeFlowIndication' => $this->total_reverted_active_power_cumulative_flow_indication,
                    'totalValueOfRevertedReactivePowerCumulativeFlowIndication' => $this->total_reverted_reactive_power_cumulative_flow_indication,
                    'positiveActiveTotalDailyPower' => $this->positive_active_total_daily_power,
                    'positiveActiveDailyPowerInPeak' => $this->positive_active_daily_power_in_peak,
                    'positiveActiveDailyPowerInFlat' => $this->positive_active_daily_power_in_flat,
                    'positiveActiveDailyPowerInValley' => $this->positive_active_daily_power_in_valley,
                    'positiveActiveDailyPowerInSpike' => $this->positive_active_daily_power_in_spike,
                    'positiveReactiveTotalDailyPower' => $this->positive_reactive_total_daily_power,
                    'revertedActiveTotalDailyPower' => $this->reverted_active_total_daily_power,
                    'revertedReactiveTotalDailyPower' => $this->reverted_reactive_total_daily_power,
                ],
            ],
        ];
    }
}
