<?php

namespace Database\Factories\Meter;

use App\Models\Meter\MeterType;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MeterType> */
class MeterTypeFactory extends Factory {
    protected $model = MeterType::class;

    /**
     * Indicate that the Meter type is for online meters that provide telemetry.
     */
    public function isOnline(): static {
        return $this->state(function (array $attributes) {
            return [
                'online' => 1,
            ];
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'online' => 0,
            'phase' => 1,
            'max_current' => 10,
        ];
    }
}
