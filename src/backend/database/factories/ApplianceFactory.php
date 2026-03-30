<?php

namespace Database\Factories;

use App\Models\Appliance;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Appliance> */
class ApplianceFactory extends Factory {
    protected $model = Appliance::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => $this->faker->word,
            'price' => $this->faker->randomDigitNotNull() * 100000,
        ];
    }
}
