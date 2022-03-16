<?php

namespace Database\Factories;

use App\Models\Meter\MeterType;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeterTypeFactory extends Factory
{
    protected $model = MeterType::class;

    public function definition(): array
    {
        return [
            'online' => $this->faker->numberBetween(0,1),
            'phase' => 1,
            'max_current' => 10,
        ];
    }
}
