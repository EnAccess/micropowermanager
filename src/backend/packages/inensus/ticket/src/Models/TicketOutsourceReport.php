<?php

namespace Inensus\Ticket\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Class OutsourceReport.
 *
 * @property int    $id
 * @property string $date
 * @property string $path
 */
class TicketOutsourceReport extends BaseModel {
    /** @use HasFactory<\Database\Factories\TicketOutsourceReportFactory> */
    use HasFactory;

    protected $table = 'ticket_outsource_reports';
}
