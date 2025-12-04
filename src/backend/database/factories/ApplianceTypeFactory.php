<?php

namespace Database\Factories;

use App\Models\ApplianceType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ApplianceType> */
class ApplianceTypeFactory extends Factory {
    protected $model = ApplianceType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => $this->faker->word,
        ];
    }
}
