<?php

namespace Database\Factories\Inensus\Ticket\Models;

use App\Models\Ticket\TicketOutsource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketOutsource> */
class TicketOutsourceFactory extends Factory {
    protected $model = TicketOutsource::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'ticket_id' => $this->faker->numberBetween(1, 10),
            'amount' => $this->faker->numberBetween(1, 10),
        ];
    }
}
