<?php

namespace Inensus\Ticket\Models;

use Database\Factories\Inensus\Ticket\Models\TicketOutsourceReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class OutsourceReport.
 *
 * @property int    $id
 * @property string $date
 * @property string $path
 */
class TicketOutsourceReport extends \App\Models\Base\BaseModel {
    /** @use HasFactory<TicketOutsourceReportFactory> */
    use HasFactory;

    protected $table = 'ticket_outsource_reports';
}
