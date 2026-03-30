<?php

namespace App\Models\Ticket;

use App\Models\Base\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int             $id
 * @property      int             $ticket_id
 * @property      int             $ticket_user_id
 * @property      string          $comment
 * @property      Carbon|null     $created_at
 * @property      Carbon|null     $updated_at
 * @property-read Ticket|null     $ticket
 * @property-read TicketUser|null $ticketUser
 */
class TicketComment extends BaseModel {
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
