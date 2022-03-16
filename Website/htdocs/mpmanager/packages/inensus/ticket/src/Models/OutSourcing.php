<?php


namespace Inensus\Ticket\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class OutSourcing
 *
 * @package Inensus\Ticket\Models
 *
 * @property int $id
 * @property int $ticket_id
 * @property int $amount
 */
class OutSourcing extends Model
{

    protected $fillable = ['amount', 'ticket_id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(config('tickets.table_names')['ticket_outsource']);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
