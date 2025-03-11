<?php

namespace Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class TicketCategory.
 *
 * @property string $label_name
 */
class TicketCategory extends BaseModel {
    use HasFactory;

    protected $table = 'ticket_categories';

    public function getLabelNameAttribute() {
        return $this->attributes['label_name'];
    }
}
