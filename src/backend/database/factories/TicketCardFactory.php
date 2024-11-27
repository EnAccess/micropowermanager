<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Models\TicketCard;

class TicketCardFactory extends Factory {
    protected $model = TicketCard::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        // includes existing list id in trello as card_id for testing
        return [
            'card_id' => '62914ca11509c55ab1c1ba56',
            'status' => 1,
        ];
    }
}
