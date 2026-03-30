<?php

namespace App\Plugins\KelinMeter\Models;

use App\Models\Base\BaseModel;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property int         $id_of_terminal
 * @property int         $id_of_measurement_point
 * @property string      $address_of_meter
 * @property string      $name_of_meter
 * @property int         $date_of_data
 * @property int         $time_of_data
 * @property float       $positive_active_value
 * @property float       $positive_reactive_value
 * @property float       $inverted_active_value
 * @property float       $inverted_reactive_value
 * @property float       $positive_active_minute
 * @property float       $positive_reactive_minute
 * @property float       $inverted_active_minute
 * @property float       $inverted_reactive_minute
 * @property float       $voltage_of_phase_a
 * @property float       $voltage_of_phase_b
 * @property float       $voltage_of_phase_c
 * @property float       $power
 * @property float       $power_factor
 * @property float       $reactive_power
 * @property float       $current_of_phase_a
 * @property float       $current_of_phase_b
 * @property float       $current_of_phase_c
 * @property float       $temperature_1
 * @property float       $temperature_2
 * @property float       $pressure_1
 * @property float       $pressure_2
 * @property float       $flow_velocity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class KelinMeterMinutelyData extends BaseModel {
    protected $table = 'kelin_meter_minutely_datas';
}
