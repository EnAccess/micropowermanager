<?php

namespace Database\Factories\Ticket;

use App\Models\Ticket\TicketUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketUser> */
class TicketUserFactory extends Factory {
    protected $model = TicketUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'user_name' => $this->faker->name,
            'phone' => $this->faker->phoneNumber(),
            'user_id' => 0,
            'out_source' => 0,
        ];
    }
}
