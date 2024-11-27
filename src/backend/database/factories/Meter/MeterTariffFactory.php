<?php

namespace Database\Factories\Meter;

use App\Models\Meter\MeterTariff;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeterTariffFactory extends Factory {
    protected $model = MeterTariff::class;

    public function definition() {
        return [
            'name' => $this->faker->randomElement(['Productive Usage', 'Household Usage', 'Commercial Usage']),
            'price' => 100000,
            'total_price' => 100000,
            'currency' => 'TZS',
            'factor' => 1,
        ];
    }
}
