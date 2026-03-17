<?php

namespace Database\Factories;

use App\Models\ApplianceRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ApplianceRate> */
class ApplianceRateFactory extends Factory {
    protected $model = ApplianceRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'appliance_person_id' => $this->faker->numberBetween(1, 10),
            'rate_cost' => $this->faker->numberBetween(1000, 50000),
            'remaining' => $this->faker->numberBetween(1000, 50000),
            'due_date' => $this->faker->date(),
            'remind' => 0,
        ];
    }
}
