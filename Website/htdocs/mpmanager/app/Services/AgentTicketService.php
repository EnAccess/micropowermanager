<?php

namespace App\Services;

use App\Exceptions\TrelloAPIException;
use App\Models\Agent;
use App\Models\Person\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Inensus\Ticket\Exceptions\TicketOwnerNotFoundException;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Services\TicketService;
use Inensus\Ticket\Trello\Tickets;


class AgentTicketService  implements IAssignationService
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
