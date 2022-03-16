<?php

namespace Database\Factories;

use App\Models\AccessRate\AccessRate;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccessRateFactory extends Factory
{
    protected $model = AccessRate::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(7500, 15000),
            'period' => $this->faker->numberBetween(7, 30),
        ];
    }
}
