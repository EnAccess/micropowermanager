<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Person\Person;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Inensus\Ticket\Exceptions\TicketOwnerNotFoundException;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Services\BoardService;
use Inensus\Ticket\Services\CardService;
use Inensus\Ticket\Services\TicketService;
use Inensus\Ticket\Trello\Tickets;

class AgentTicketService implements IAgentRelatedService
{
    private $boardService;
    private $cardService;
    private $tickets;
    /**
     * @var TicketService
     */
    private $ticketsService;

    /**
     * AgentTicketService constructor.
     *
     * @param BoardService $boardService
     * @param CardService  $cardService
     */
    public function __construct(
        BoardService $boardService,
        CardService $cardService,
        TicketService $ticketsService,
        Tickets $tickets
    ) {
        $this->boardService = $boardService;
        $this->cardService = $cardService;
        $this->tickets = $tickets;
        $this->ticketsService = $ticketsService;
    }

    /**
     * @return LengthAwarePaginator
     */
    public function list($agentId)
    {
        $tickets = Ticket::with('category', 'owner')
            ->whereHasMorph(
                'creator',
                [Agent::class],
                static function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            )
            ->latest()
            ->paginate(5);


        return $this->getTicketDetailsFromSource($tickets);
    }

    public function listByCustomer($agentId, $customerId): LengthAwarePaginator
    {
        return Ticket::with(['category','owner'])
            ->whereHasMorph(
                'creator',
                [Agent::class],
                static function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            )
            ->where('owner_id', $customerId)
            ->latest()
            ->paginate();
    }


    protected function getTicketDetailsFromSource(LengthAwarePaginator $ticketList): LengthAwarePaginator
    {
        $ticket_ids = $ticketList->getCollection();
        if ($ticketList->count()) {
            //get ticket details from trello
            $ticketData = $this->ticketsService->getBatch($ticket_ids);
            $ticketList->setCollection(Collection::make($ticketData));
        }

        return $ticketList;
    }

    /**
     * @param  $ticketData
     * @return mixed
     * @throws TicketOwnerNotFoundException
     */
    public function create(array $ticketData)
    {
        $board = $this->boardService->initializeBoard();
        $card = $this->cardService->initalizeList($board);

        $ownerId = (int)$ticketData['owner_id'];
        try {
            $owner = Person::query()->findOrFail($ownerId);
        } catch (ModelNotFoundException $e) {
            throw new TicketOwnerNotFoundException("Ticket owner with following id not found " . $ownerId);
        }

        $creator = request()->attributes->get('user');

        //reformat due date if it is set
        $dueDate = isset($ticketData['due_date']) ? date('Y-m-d H:i:00', strtotime($ticketData['due_date'])) : null;
        $categoryId = $ticketData['label'];


        $trelloParams = [
            'idList' => $card->card_id,
            'name' => $ticketData['title'],
            'desc' => $ticketData['description'],
            'due' => $dueDate === '1970-01-01' ? null : $dueDate,
        ];

        $ticketId = $this->tickets->createTicket($trelloParams)->id;

        $ticket = Ticket::make(
            [
            'ticket_id' => $ticketId,
            'category_id' => $categoryId,
            'creator_type' => 'agent',
            'creator_id' => $creator->id
            ]
        );

        $ticket->owner()->associate($owner);
        $ticket->save();
        return $ticket;
    }

    /**
     * @param array $tickets
     * @return array
     */
    public function getBatch(array $tickets): array
    {
        $ticketData = [];

        foreach ($tickets as $index => $ticket) {
            $tickets[$index]['ticket'] = $this->getTicket($ticket->ticket_id);
            $tickets[$index]['actions'] = $this->getActions($ticket->ticket_id);
            //$t['self'] = $ticket;
        }

        return $tickets;
    }

    /**
     * @param $ticketId
     * @return Builder|Model|null
     */
    public function getTicket($ticketId)
    {
        $ticket = Ticket::with('category', 'owner')->where('ticket_id', $ticketId)->first();
        if ($ticket !== null) {
            $ticket->ticket = $this->ticketsService->getTicket($ticketId);
            $ticket->actions = $this->ticketsService->getActions($ticketId);
        }

        return $ticket;
    }

    public function getActions($ticketId)
    {
        return $this->tickets->actions($ticketId);
    }
}
