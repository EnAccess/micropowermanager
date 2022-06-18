<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 20.08.18
 * Time: 14:57
 */

namespace Inensus\Ticket\Models;


class TicketBoard extends BaseModel
{

    protected $table = 'ticket_boards';

    public function tickets()
    {
        return $this->hasMany(TicketCard::class);
    }

}
