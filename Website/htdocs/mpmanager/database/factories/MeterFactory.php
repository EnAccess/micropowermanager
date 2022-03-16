<?php

namespace Database\Factories;

use App\Models\Meter\Meter;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;


class MeterFactory extends Factory {
    protected $model = Meter::class;

    public function definition()
    {
        return [
            'meter_type_id' => $this->faker->randomNumber(1),
            'in_use' => false,
            'manufacturer_id' => 1,
            'serial_number' => str_random(36),
        ];
    }
}
