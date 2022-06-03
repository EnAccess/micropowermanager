<?php


namespace Inensus\Ticket\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * Class OutsourceReport
 *
 * @package Inensus\Ticket\Models
 *
 * @property int $id
 * @property string $date
 * @property string $path
 */
class TicketOutsourceReport extends BaseModel
{

    protected $table = 'ticket_outsource_reports';
}
