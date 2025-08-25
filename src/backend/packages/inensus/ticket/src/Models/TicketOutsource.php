<?php

namespace Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class TicketOutsource extends BaseModel {
    /** @use HasFactory<\Database\Factories\TicketOutsourceFactory> */
    use HasFactory;

    protected $table = 'ticket_outsources';

    public function ticket() {
        return $this->belongsTo(Ticket::class);
    }
}
