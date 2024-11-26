<?php

namespace Inensus\Ticket\Models;

class TicketOutsource extends BaseModel {
    protected $table = 'ticket_outsources';

    public function ticket() {
        return $this->belongsTo(Ticket::class);
    }
}
