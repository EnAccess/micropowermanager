<?php

namespace Database\Factories\Meter;

use App\Models\Meter\MeterTariff;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MeterTariff> */
class MeterTariffFactory extends Factory {
    protected $model = MeterTariff::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => $this->faker->randomElement(['Productive Usage', 'Household Usage', 'Commercial Usage']),
            'price' => 100000,
            'total_price' => 100000,
            'currency' => 'TZS',
            'factor' => 1,
        ];
    }
}
