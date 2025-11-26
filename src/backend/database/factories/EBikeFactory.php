<?php

namespace Database\Factories;

use App\Models\EBike;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EBike> */
class EBikeFactory extends Factory {
    protected $model = EBike::class;

    public function definition(): array {
        return [
            'serial_number' => $this->faker->unique()->uuid,
            'manufacturer_id' => 1,
            'receive_time' => now()->subMinutes(rand(1, 1000)),
            'speed' => $this->faker->randomFloat(1, 0, 45),
            'mileage' => $this->faker->randomFloat(1, 0, 10000),
            'status' => $this->faker->randomElement(['active', 'idle', 'offline']),
            'soh' => $this->faker->randomElement(['good', 'ok', 'bad']),
            'battery_level' => $this->faker->randomFloat(1, 0, 100),
            'battery_voltage' => $this->faker->randomFloat(1, 42, 54),
        ];
    }
}
