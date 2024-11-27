<?php

namespace App\Services;

use App\Models\MaintenanceUsers;
use App\Services\Interfaces\IAssignationService;
use Inensus\Ticket\Models\Ticket;

/**
 * @implements IAssignationService<Ticket, MaintenanceUsers>
 */
class MaintenanceUserTicketService implements IAssignationService {
    private Ticket $ticket;
    private MaintenanceUsers $maintenanceUser;

    public function setAssigned($ticket): void {
        $this->ticket = $ticket;
    }

    public function setAssignee($maintenanceUser): void {
        $this->maintenanceUser = $maintenanceUser;
    }

    public function assign(): Ticket {
        $this->ticket->owner()->associate($this->maintenanceUser);

        return $this->ticket;
    }
}
