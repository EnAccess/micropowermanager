<?php


namespace Inensus\Ticket\Services;


use App\Exceptions\TrelloAPIException;
use App\Models\Agent;
use App\Services\BaseService;
use App\Services\IAssociative;
use App\Services\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Trello\Tickets;
use function Symfony\Component\Translation\t;

class TicketService extends BaseService implements IBaseService,IAssociative
{

    public function __construct(private Tickets $trelloAPI, private Ticket $ticket)
    {
        parent::__construct([$ticket]);
    }

    public function create($trelloTicketData = [])
    {

        try {
            $trelloTicket = $this->trelloAPI->createTicket($trelloTicketData);
        } catch (\Exception $exception) {
            Log::error('An unexpected error occurred at trello ticket creation.',
                ['message' => $exception->getMessage()]);

            throw new TrelloAPIException($exception->getMessage());
        }

        return $trelloTicket;
    }

    public function close($ticketId)
    {
        try {
            $closeRequest = $this->trelloAPI->closeTicket($ticketId);
        } catch (\Exception $exception) {
            Log::error('An unexpected error occurred at trello ticket closing.',
                ['message' => $exception->getMessage()]);

            throw new TrelloAPIException($exception->getMessage());
        }

        $ticket = $this->getById($ticketId);
        $ticketData = ['status'=>1];
        $this->update($ticket, $ticketData);

        return $closeRequest;
    }

    public function getTicket($ticketId)
    {
        try {
            return $this->trelloAPI->get($ticketId);
        } catch (\Exception $exception) {
            Log::error('An unexpected error occurred at getting ticket from trello API.',
                ['message' => $exception->getMessage()]);

            throw new TrelloAPIException($exception->getMessage());
        }
    }

    public function getActions($ticketId)
    {
        try {
            return $this->trelloAPI->actions($ticketId);
        } catch (\Exception $exception) {
            Log::error('An unexpected error occurred at getting actions from trello API.',
                ['message' => $exception->getMessage()]);

            throw new TrelloAPIException($exception->getMessage());
        }
    }

    public function getBatch($tickets)
    {
        foreach ($tickets as $index => $ticket) {

            $tickets[$index]['ticket'] = $this->getTicket($ticket->ticket_id);
            $tickets[$index]['actions'] = $this->getActions($ticket->ticket_id);
            //$t['self'] = $ticket;
        }

        return $tickets;
    }

    public function getById($ticketId)
    {
        $ticket = $this->ticket->newQuery()->with(['category', 'owner'])->where('ticket_id', $ticketId)->first();

        if ($ticket !== null) {
            $ticket->ticket = $this->getTicket($ticketId);
            $ticket->actions = $this->getActions($ticketId);
        }

        return $ticket;
    }

    public function update($ticket, $ticketData)
    {
        $ticket->update($ticketData);
        $ticket->fresh();

        return $ticket;
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }

    public function getAll($limit = null, $agentId = null, $customerId = null)
    {
        $tickets = $this->ticket->newQuery()->with(['category', 'owner']);
        if ($agentId) {
            $tickets->whereHasMorph(
                'creator',
                [Agent::class],
                static function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            );
        }

        if ($customerId)
        {
            $tickets ->where('owner_id', $customerId);
        }

        if ($limit)
        {
            $tickets->paginate($limit);
        }else{
            $tickets->get();
        }

        if ($tickets->count()) {
            //get ticket details from trello
            $ticketData = $this->getBatch($tickets);
            $tickets->setCollection(Collection::make($ticketData));
        }

        return $tickets;
    }

    public function make($ticketData)
    {
        return $this->ticket->newQuery()->make($ticketData);
    }

    public function save($ticket)
    {
       return $ticket->save();
    }
}
