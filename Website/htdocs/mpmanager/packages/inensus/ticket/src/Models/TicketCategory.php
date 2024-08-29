<?php

namespace Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Inensus\Ticket\Factories\TicketCategoryFactory;

class TicketCategory extends BaseModel
{
    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return TicketCategoryFactory::new();
    }

    protected $table = 'ticket_categories';
}
