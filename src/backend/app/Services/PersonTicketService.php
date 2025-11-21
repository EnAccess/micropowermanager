<?php

namespace App\Services;

use App\Models\Person\Person;
use App\Models\Ticket\Ticket;
use App\Services\Interfaces\IAssignationService;

/**
 * @implements IAssignationService<Ticket, Person>
 */
class PersonTicketService implements IAssignationService {
    private Ticket $ticket;
    private Person $person;

    public function setAssigned($ticket): void {
        $this->ticket = $ticket;
    }

    public function setAssignee($person): void {
        $this->person = $person;
    }

    public function assign(): Ticket {
        $this->ticket->owner()->associate($this->person);

        return $this->ticket;
    }
}
