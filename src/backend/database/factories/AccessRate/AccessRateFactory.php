<?php

namespace Database\Factories\AccessRate;

use App\Models\AccessRate\AccessRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccessRateFactory extends Factory {
    protected $model = AccessRate::class;

    public function definition(): array {
        return [
            'amount' => $this->faker->randomElement([7500, 15000]),
            'period' => $this->faker->randomElement([7, 30]),
        ];
    }
}
