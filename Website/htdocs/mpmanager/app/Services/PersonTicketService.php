<?php

namespace App\Services;

use App\Models\Person\Person;
use Inensus\Ticket\Models\Ticket;

class PersonTicketService implements IAssignationService
{
    private Ticket $ticket;
    private Person $person;

    public function setAssigned($ticket)
    {
        $this->ticket = $ticket;
    }

    public function setAssigner($person)
    {
        $this->person = $person;
    }

    public function assign()
    {
        $this->ticket->owner()->associate($this->person);

        return $this->ticket;
    }
}
