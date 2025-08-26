<?php

namespace Database\Factories\Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Models\Ticket;

/** @extends Factory<Ticket> */
class TicketFactory extends Factory {
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'creator_id' => $this->faker->numberBetween(1, 10),
            'creator_type' => $this->faker->randomElement(['user', 'agent']),
            'assigned_id' => $this->faker->numberBetween(1, 10),
            'owner_id' => $this->faker->numberBetween(1, 10),
            'owner_type' => 'person',
            'category_id' => $this->faker->numberBetween(1, 10),
            'status' => $this->faker->randomElement([0, 1]),
        ];
    }
}
