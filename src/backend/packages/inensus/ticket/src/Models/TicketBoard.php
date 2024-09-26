<?php

namespace Inensus\Ticket\Models;

class TicketBoard extends BaseModel
{
    protected $table = 'ticket_boards';

    public function tickets()
    {
        return $this->hasMany(TicketCard::class);
    }
}
