<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Models\TicketBoard;

class TicketBoardFactory extends Factory
{
    protected $model = TicketBoard::class;
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // includes existing board id in trello for testing
        return [
            'board_id'=> '6291424c4e11631cfad78a37',
            'web_hook_id'=> strval($this->faker->uuid),
            'active'=> 1,
        ];
    }
}
