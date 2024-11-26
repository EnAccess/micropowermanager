<?php

namespace Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketComment extends BaseModel {
    protected $table = 'ticket_comments';

    public function ticket(): BelongsTo {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    public function ticketUser(): BelongsTo {
        return $this->belongsTo(TicketUser::class, 'ticket_user_id', 'id');
    }
}
