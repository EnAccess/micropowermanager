<?php

namespace Inensus\Ticket\Models;

use App\Models\Base\BaseModel;
use Database\Factories\Inensus\Ticket\Models\TicketOutsourceReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * Class OutsourceReport.
 *
 * @property int         $id
 * @property string      $date
 * @property string      $path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TicketOutsourceReport extends BaseModel {
    /** @use HasFactory<TicketOutsourceReportFactory> */
    use HasFactory;

    protected $table = 'ticket_outsource_reports';
}
