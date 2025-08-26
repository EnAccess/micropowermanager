<?php

namespace Database\Factories;

use App\Models\MaintenanceUsers;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MaintenanceUsers> */
class MaintenanceUsersFactory extends Factory {
    protected $model = MaintenanceUsers::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [];
    }
}
