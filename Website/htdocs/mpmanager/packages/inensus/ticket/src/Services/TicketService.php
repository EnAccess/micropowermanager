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

class TicketService extends BaseService implements IBaseService, IAssociative
{

    public function __construct(private Tickets $trelloAPI, private Ticket $ticket)
    {
        parent::__construct([$ticket]);
    }

    public function create($trelloTicketData = [])
    {
        try {
            $trelloTicket = $this->trelloAPI->createTicket($trelloTicketData);
        } catch (TrelloAPIException $exception) {
            Log::error('An unexpected error occurred at trello ticket creation.',
                ['message' => $exception->getMessage()]);

            throw new TrelloAPIException($exception->getMessage());
        }

        return $trelloTicket;
    }

    public function close($ticketId)
    {
        try {
            $ticket = $this->getById($ticketId);
            $closeRequest = $this->trelloAPI->closeTicket($ticket->ticket_id);
            $ticketData = ['status' => 1];
            $this->update($ticket, $ticketData);

            return $closeRequest;
        } catch (TrelloAPIException $exception) {
            Log::error('An unexpected error occurred at trello ticket closing.',
                ['message' => $exception->getMessage()]);

            throw new TrelloAPIException($exception->getMessage());
        }
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
        } catch (TrelloAPIException $exception) {
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

    public function getByTrelloId($trelloId)
    {
        $ticket = $this->ticket->newQuery()->with(['category', 'owner'])->where('ticket_id', $trelloId)->first();

        if ($ticket !== null) {
            $ticket->ticket = $this->getTicket($trelloId);
            $ticket->actions = $this->getActions($trelloId);
        }

        return $ticket;
    }

    public function getById($ticketId)
    {
        return $this->ticket->newQuery()->with(['category', 'owner'])->where('id', $ticketId)->first();
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

    public function getAll($limit = null, $status = null, $agentId = null, $customerId = null,
        $assignedId = null, $categoryId = null)
    {
        $query = $this->ticket->newQuery()->with(['category', 'owner','assignedTo']);

        if ($agentId) {
            $query->whereHasMorph(
                'creator',
                [Agent::class],
                static function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            );
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($customerId) {
            $query->where('owner_id', $customerId);
        }

        if ($categoryId){
            $query->where('category_id', $categoryId);
        }

        if ($assignedId){
            $query->where('assigned_id', $assignedId);
        }

        if ($limit) {
            $tickets = $query->paginate($limit);
        } else {

            $tickets = $query->paginate();
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

    public function getForOutsourceReport($startDate, $endDate)
    {
      return  $this->ticket->newQuery()->with(['outsource', 'assignedTo', 'category'])
            ->whereHas('category', static function ($q) {
                $q->where('out_source', 1);
            })
            ->whereHas('assignedTo', static function ($q) {
                $q->whereNotNull('owner_id');
            })
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }
}
