<?php

namespace App\Services;

use App\Models\Ticket\TicketOutsourcePayoutReport;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<TicketOutsourcePayoutReport>
 */
class TicketOutsourcePayoutReportService implements IBaseService {
    /** @use HasCrudOperations<TicketOutsourcePayoutReport> */
    use HasCrudOperations;

    public function __construct(
        private TicketOutsourcePayoutReport $TicketOutsourcePayoutReport,
    ) {}

    protected function crudModel(): TicketOutsourcePayoutReport {
        return $this->TicketOutsourcePayoutReport;
    }
}
