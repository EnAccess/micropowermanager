<?php

namespace Database\Factories;

use App\Models\ConnectionGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ConnectionGroup> */
class ConnectionGroupFactory extends Factory {
    protected $model = ConnectionGroup::class;

    public function definition(): array {
        return [
            'name' => $this->faker->name,
        ];
    }
}
