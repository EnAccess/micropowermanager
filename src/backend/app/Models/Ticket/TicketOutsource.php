<?php

namespace App\Models\Ticket;

use App\Models\Base\BaseModel;
use Database\Factories\Inensus\Ticket\Models\TicketOutsourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property      int         $id
 * @property      int         $ticket_id
 * @property      int         $amount
 * @property      Carbon|null $created_at
 * @property      Carbon|null $updated_at
 * @property-read Ticket|null $ticket
 */
class TicketOutsource extends BaseModel {
    /** @use HasFactory<TicketOutsourceFactory> */
    use HasFactory;

    protected $table = 'ticket_outsources';

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo {
        return $this->belongsTo(Ticket::class);
    }
}
