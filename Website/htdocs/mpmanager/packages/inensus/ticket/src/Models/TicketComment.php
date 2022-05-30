<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 26.09.18
 * Time: 16:00
 */

use Inensus\Ticket\Models\BaseModel;
use Inensus\Ticket\Models\TicketCard;

/**
 * Class Comment
 *
 */
class TicketComment extends BaseModel
{

   public function ticket()
    {
        return $this->belongsTo(TicketCard::class);
    }
}
