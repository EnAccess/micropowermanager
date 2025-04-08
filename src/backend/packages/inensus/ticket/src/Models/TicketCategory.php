<?php

namespace Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $label_name
 */
class TicketCategory extends BaseModel {
    use HasFactory;

    protected $table = 'ticket_categories';
}
