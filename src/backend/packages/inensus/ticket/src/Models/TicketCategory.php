<?php

namespace Inensus\Ticket\Models;

use Database\Factories\Inensus\Ticket\Models\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int    $id
 * @property string $label_name
 * @property string $label_color
 * @property int    $out_source
 */
class TicketCategory extends BaseModel {
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $table = 'ticket_categories';
}
