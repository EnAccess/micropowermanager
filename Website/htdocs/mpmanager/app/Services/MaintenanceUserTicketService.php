<?php

namespace App\Services;

use App\Models\MaintenanceUsers;
use Inensus\Ticket\Models\Ticket;

class MaintenanceUserTicketService implements IAssignationService
{
    private Ticket $ticket;
    private MaintenanceUsers $maintenanceUser;

    public function setAssigned($ticket)
    {
        $this->ticket = $ticket;
    }

    public function setAssignee($maintenanceUser)
    {
        $this->maintenanceUser = $maintenanceUser;
    }

    public function assign()
    {
        $this->ticket->owner()->associate($this->maintenanceUser);

        return $this->ticket;
    }
}
