<?php

namespace Inensus\Ticket\Models;

use Carbon\Carbon;
use Database\Factories\Inensus\Ticket\Models\TicketOutsourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property Ticket $ticket
 * @property int    $amount
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
