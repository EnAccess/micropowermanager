<?php

namespace Database\Factories;

use App\Models\Tariff;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Tariff> */
class TariffFactory extends Factory {
    protected $model = Tariff::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => $this->faker->randomElement(['Productive Usage', 'Household Usage', 'Commercial Usage']),
            'price' => 100000,
            'currency' => 'TZS',
            'factor' => 1,
        ];
    }
}
