<?php

namespace App\Models\Ticket;

use App\Models\Base\BaseModel;
use Database\Factories\Inensus\Ticket\Models\TicketOutsourcePayoutReportFactory;
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
class TicketOutsourcePayoutReport extends BaseModel {
    /** @use HasFactory<TicketOutsourcePayoutReportFactory> */
    use HasFactory;

    protected $table = 'ticket_outsource_payout_reports';
}
