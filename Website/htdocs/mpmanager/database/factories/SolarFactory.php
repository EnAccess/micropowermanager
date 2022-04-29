<?php

namespace Database\Factories;

use App\Models\Solar;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

class SolarFactory extends Factory {
    protected $model= Solar::class;

    public function definition(): array
    {
        return [
            'node_id' => 1,
            'device_id' => 'test-case',
            'mini_grid_id' => 1,
            'time_stamp' => $this->faker->dateTime(),
            'starting_time' => $this->faker->numerify('##########'),
            'ending_time' => $this->faker->numerify('##########'),
            'min' => $this->faker->numberBetween(0, 100),
            'max' => $this->faker->numberBetween(0, 100),
            'average' => $this->faker->numberBetween(0, 100),
            'duration' => 300,
            'readings' => $this->faker->numberBetween(200, 300),

        ];
    }
}
