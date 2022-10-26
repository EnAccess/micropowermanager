<?php

namespace App\Services;

use App\Models\Agent;
use Inensus\Ticket\Models\Ticket;

class AgentTicketService implements IAssignationService
{
    private Agent $agent;
    private Ticket $ticket;

    public function setAssigned($ticket)
    {
        $this->ticket = $ticket;
    }

    public function setAssigner($agent)
    {
        $this->agent = $agent;
    }

    public function assign()
    {
        $this->ticket->creator()->associate($this->agent);

        return $this->ticket;
    }
}
