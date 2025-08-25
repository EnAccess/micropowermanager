<?php

namespace Inensus\Ticket\Models;

use Database\Factories\Inensus\Ticket\Models\TicketOutsourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketOutsource extends BaseModel {
    /** @use HasFactory<TicketOutsourceFactory> */
    use HasFactory;

    protected $table = 'ticket_outsources';

    public function ticket() {
        return $this->belongsTo(Ticket::class);
    }
}
