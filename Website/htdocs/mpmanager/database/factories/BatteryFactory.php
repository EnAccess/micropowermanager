<?php

namespace Database\Factories;

use App\Models\Battery;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BatteryFactory extends Factory
{
    protected $model = Battery::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'mini_grid_id' => 1,
            'node_id' => 1,
            'device_id' => "test-case",
            'read_out' => $this->faker->dateTime(),
            'battery_count' => 2,
            'soc_average' => $this->faker->numberBetween(40, 100),
            'soc_unit' => '%',
            'soc_min' => $this->faker->numberBetween(40, 100),
            'soc_max' => $this->faker->numberBetween(40, 100),
            'soh_average' =>$this->faker->numberBetween(80, 100),
            'soh_unit' => '%',
            'soh_min' => 100,
            'soh_max' => 100,
            'd_total' => $this->faker->numberBetween(13500, 14000),
            'd_total_unit' => 'MWh',
            'd_newly_energy' => 0,
            'd_newly_energy_unit' => 'Wh',
            'active' => 1,
            'c_total' => $this->faker->numberBetween(55, 60),
            'c_total_unit' => 'MWh',
            'c_newly_energy' => $this->faker->numberBetween(0, 4000000),
            'c_newly_energy_unit' => 'Wh',
            'temperature_min' => 25,
            'temperature_max' => 26,
            'temperature_average' => 26,
            'temperature_unit' => '°C',

        ];
    }
}
