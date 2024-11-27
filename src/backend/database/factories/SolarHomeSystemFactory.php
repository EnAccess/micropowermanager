<?php

namespace Database\Factories;

use App\Models\SolarHomeSystem;
use Illuminate\Database\Eloquent\Factories\Factory;

class SolarHomeSystemFactory extends Factory {
    protected $model = SolarHomeSystem::class;

    public function definition(): array {
        return [
            // 'asset_id' => 1,
            'serial_number' => $this->faker->unique()->uuid,
            'manufacturer_id' => 1,
        ];
    }
}
