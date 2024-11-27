<?php

namespace App\Services;

use App\Models\Agent;
use App\Services\Interfaces\IAssignationService;
use Inensus\Ticket\Models\Ticket;

/**
 * @implements IAssignationService<Ticket, Agent>
 */
class AgentTicketService implements IAssignationService {
    private Ticket $ticket;
    private Agent $agent;

    public function setAssigned($ticket): void {
        $this->ticket = $ticket;
    }

    public function setAssignee($agent): void {
        $this->agent = $agent;
    }

    public function assign(): Ticket {
        $this->ticket->creator()->associate($this->agent);

        return $this->ticket;
    }
}
