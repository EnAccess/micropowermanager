<?php

namespace Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int    $id
 * @property string $label_name
 * @property string $label_color
 * @property int    $out_source
 */
class TicketCategory extends BaseModel {
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    protected $table = 'ticket_categories';
}
