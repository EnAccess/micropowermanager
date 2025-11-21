<?php

namespace App\Models\Ticket;

use App\Models\Base\BaseModel;
use Database\Factories\TicketTicketCategoryFactory;
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
    /** @use HasFactory<TicketCategoryFactory> */
    use HasFactory;

    protected $table = 'ticket_categories';
}
