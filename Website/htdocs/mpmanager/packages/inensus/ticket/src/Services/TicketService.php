<?php
/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 23.08.18
 * Time: 17:09
 */

namespace Inensus\Ticket\Services;


use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Trello\Tickets;

class TicketService
{
    private $tickets;
    private $ticketModel;

    public function __construct(Tickets $tickets, Ticket $ticketModel)
    {
        $this->ticketModel = $ticketModel;
        $this->tickets = $tickets;
    }

    public function create($creatorId, $creatorType,$ownerId, $ownerType, $category, $assignedId, array $data = [])
    {
        $ticket = $this->tickets->createTicket($data);
        return $this->saveTicket($ticket, $ownerType, $ownerId, $creatorId, $category, $assignedId,$creatorType);
    }

    public function close($ticketId)
    {

        $closeRequest = $this->tickets->closeTicket($ticketId);

        $ticket = $this->ticketModel::where('ticket_id', $ticketId)->first();
        $ticket->status = 1;
        $ticket->save();

        return $closeRequest;
    }

    private function saveTicket($ticketData, $ownerType, $ownerId, $creatorId, $category, $assignedId,$creatorType): Ticket
    {
        $ticket = new Ticket();
        $ticket->ticket_id = $ticketData->id;
        $ticket->owner_type = $ownerType;
        $ticket->owner_id = $ownerId;
        $ticket->creator_id = $creatorId;
        $ticket->creator_type =$creatorType;
        $ticket->category_id = $category;
        $ticket->assigned_id = $assignedId;
        $ticket->save();
        return $ticket;
    }

    public function getTicket($ticketId)
    {
        return $this->tickets->get($ticketId);
    }

    public function getActions($ticketId)
    {
        return $this->tickets->actions($ticketId);
    }

    public function getBatch($tickets)
    {
        $ticketData = [];

        foreach ($tickets as $index => $ticket) {

            $tickets[$index]['ticket'] = $this->getTicket($ticket->ticket_id);
            $tickets[$index]['actions'] = $this->getActions($ticket->ticket_id);
            //$t['self'] = $ticket;


        }

        return $tickets;

    }
}
