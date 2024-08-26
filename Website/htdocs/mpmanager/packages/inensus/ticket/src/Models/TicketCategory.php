<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 06.09.18
 * Time: 14:50.
 */

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
