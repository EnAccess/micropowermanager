<?php

namespace Database\Factories;

use App\Models\Meter\MeterParameter;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeterParameterFactory extends Factory
{
    protected $model = MeterParameter::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'owner_type' => 'person',
            'owner_id' => $this->faker->randomNumber(1),
            'meter_id' => $this->faker->randomNumber(1),
            'tariff_id' => $this->faker->randomNumber(1),
            'connection_type_id' => $this->faker->randomNumber(1),
            'connection_group_id' => $this->faker->randomNumber(1),
        ];
    }
}
