<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Models\TicketOutsource;

class TicketOutsourceFactory extends Factory {
    protected $model = TicketOutsource::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'ticket_id' => $this->faker->numberBetween(1, 10),
            'amount' => $this->faker->numberBetween(1, 10),
        ];
    }
}
