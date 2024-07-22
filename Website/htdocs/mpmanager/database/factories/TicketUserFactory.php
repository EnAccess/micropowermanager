<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Models\TicketUser;

class TicketUserFactory extends Factory
{
    protected $model = TicketUser::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_name' => $this->faker->name,
            'user_tag' => $this->faker->word,
            'out_source' => 0,
            'extern_id' => $this->faker->word,
        ];
    }
}
