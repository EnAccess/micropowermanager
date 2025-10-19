<?php

namespace Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketComment extends \App\Models\Base\BaseModel {
    protected $table = 'ticket_comments';

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo {
        return $this->belongsTo(Ticket::class, 'ticket_id', 'id');
    }

    /**
     * @return BelongsTo<TicketUser, $this>
     */
    public function ticketUser(): BelongsTo {
        return $this->belongsTo(TicketUser::class, 'ticket_user_id', 'id');
    }
}
