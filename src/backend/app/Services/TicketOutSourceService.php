<?php

namespace App\Services;

use App\Models\Ticket\TicketOutsource;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;

/**
 * @implements IBaseService<TicketOutsource>
 */
class TicketOutSourceService implements IBaseService {
    /** @use HasCrudOperations<TicketOutsource> */
    use HasCrudOperations;

    public function __construct(
        private TicketOutsource $ticketOutsource,
    ) {}

    protected function crudModel(): TicketOutsource {
        return $this->ticketOutsource;
    }
}
