<?php

namespace App\Services;

use App\Models\User;
use Inensus\Ticket\Models\Ticket;

class UserTicketService implements IAssignationService
{
    private User $user;
    private Ticket $ticket;

    public function setAssigned($ticket)
    {
        $this->ticket = $ticket;
    }

    public function setAssignee($user)
    {
        $this->user = $user;
    }

    public function assign()
    {
        $this->ticket->creator()->associate($this->user);

        return $this->ticket;
    }
}
