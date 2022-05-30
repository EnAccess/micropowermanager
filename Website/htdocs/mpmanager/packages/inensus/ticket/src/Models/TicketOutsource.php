<?php


namespace Inensus\Ticket\Models;


use Illuminate\Database\Eloquent\Model;


class TicketOutsource extends BaseModel
{
    protected $connection = 'test_company_db';
    protected $table = 'ticket_outsources';


    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
