<?php

namespace Database\Factories;

use App\Models\ConnectionType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConnectionTypeFactory extends Factory {
    protected $model = ConnectionType::class;

    public function definition(): array {
        return [
            'name' => $this->faker->name,
        ];
    }
}
