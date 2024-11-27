<?php

namespace Database\Factories\Meter;

use App\Models\Meter\Meter;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeterFactory extends Factory {
    protected $model = Meter::class;

    public function definition() {
        return [
            'meter_type_id' => $this->faker->randomNumber(1),
            'in_use' => false,
            'manufacturer_id' => 1,
            'serial_number' => $this->faker->unique()->uuid,
        ];
    }
}
