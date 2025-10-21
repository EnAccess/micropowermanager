<?php

namespace Inensus\Ticket\Models;

use App\Models\Base\BaseModel;
use Database\Factories\Inensus\Ticket\Models\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * @property int         $id
 * @property string      $label_name
 * @property string      $label_color
 * @property bool        $out_source
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TicketCategory extends BaseModel {
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected $table = 'ticket_categories';
}
