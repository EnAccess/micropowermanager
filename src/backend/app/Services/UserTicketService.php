<?php

namespace App\Services;

use App\Models\User;
use App\Services\Interfaces\IAssignationService;
use Inensus\Ticket\Models\Ticket;

/**
 * @implements IAssignationService<Ticket, User>
 */
class UserTicketService implements IAssignationService {
    private Ticket $ticket;
    private User $user;

    public function setAssigned($ticket): void {
        $this->ticket = $ticket;
    }

    public function setAssignee($user): void {
        $this->user = $user;
    }

    public function assign(): Ticket {
        $this->ticket->creator()->associate($this->user);

        return $this->ticket;
    }
}
