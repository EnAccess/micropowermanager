<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Models\TicketUser;

class TicketUserFactory extends Factory {
    protected $model = TicketUser::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'user_name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber(),
            'user_id' => 0,
            'out_source' => 0,
        ];
    }
}
