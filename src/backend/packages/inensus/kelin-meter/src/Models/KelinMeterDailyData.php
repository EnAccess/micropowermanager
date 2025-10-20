<?php

namespace Inensus\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Carbon\Carbon;

/**
 * @property int    $id
 * @property int    $id_of_terminal
 * @property int    $id_of_measurement_point
 * @property string $address_of_meter
 * @property string $name_of_meter
 * @property int    $date_of_data
 * @property float  $total_positive_active_power_cumulative_flow_indication
 * @property float  $total_positive_active_peak_power
 * @property float  $total_positive_active_flat_power
 * @property float  $total_positive_active_valley_power
 * @property float  $total_positive_active_spike_power
 * @property float  $total_positive_reactive_power_cumulative_flow_indication
 * @property float  $total_positive_reactive_peak_power
 * @property float  $total_positive_reactive_flat_power
 * @property float  $total_positive_reactive_valley_power
 * @property float  $total_positive_reactive_spike_power
 * @property float  $total_reverted_active_power_cumulative_flow_indication
 * @property float  $total_reverted_reactive_power_cumulative_flow_indication
 * @property float  $positive_active_total_daily_power
 * @property float  $positive_active_daily_power_in_peak
 * @property float  $positive_active_daily_power_in_flat
 * @property float  $positive_active_daily_power_in_valley
 * @property float  $positive_active_daily_power_in_spike
 * @property float  $positive_reactive_total_daily_power
 * @property float  $reverted_active_total_daily_power
 * @property float  $reverted_reactive_total_daily_power
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class KelinMeterDailyData extends BaseModel {
    protected $table = 'kelin_meter_daily_datas';
}
