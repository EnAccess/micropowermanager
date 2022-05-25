<?php

namespace App\Http\Controllers;

use App\Exceptions\TrelloAPIException;
use App\Http\Requests\CreateAgentTicketRequest;
use App\Http\Resources\ApiResource;
use App\Services\AgentService;
use App\Services\AgentTicketService;
use App\Services\PersonService;
use App\Services\PersonTicketService;
use Illuminate\Http\Request;
use Inensus\Ticket\Exceptions\TicketOwnerNotFoundException;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Models\Board;
use Inensus\Ticket\Services\BoardService;
use Inensus\Ticket\Services\CardService;
use Inensus\Ticket\Services\TicketService;


class AgentTicketController extends Controller
{
    private $board;
    private $card;

    public function __construct(
        private BoardService $boardService,
        private CardService $cardService,
        private AgentTicketService $agentTicketService,
        private PersonTicketService $personTicketService,
        private AgentService $agentService,
        private TicketService $ticketService,
        private PersonService $personService,

    ) {
        $this->board = $this->boardService->initializeBoard();
        $this->card = $this->cardService->initalizeList($this->board);
    }

    public function index(Request $request): ApiResource
    {
        $agent = $this->agentService->getByAuthenticatedUser();
        $limit = $request->input('limit', 5);

        return ApiResource::make($this->ticketService->getAll($limit, $agent->id));
    }

    public function show($ticketId, Request $request): ApiResource
    {
        return ApiResource::make($this->ticketService->getById($ticketId));
    }

    public function store(CreateAgentTicketRequest $request): TicketResource
    {
        $ticketData = $request->only([
            'owner_id',
            'due_date',
            'label',
            'title',
            'description',
            'assignedId'
        ]);
        $ownerId = $ticketData['owner_id'];
        $owner = $this->personService->getById($ownerId);

        if (!$owner) {
            throw new TicketOwnerNotFoundException('Ticket owner with following id not found ' . $ownerId);
        }

        $agent = $this->agentService->getByAuthenticatedUser();
        //reformat due date if it is set
        $dueDate = isset($ticketData['due_date']) ? date('Y-m-d H:i:00', strtotime($ticketData['due_date'])) : null;
        $categoryId = $ticketData['label'];

        $trelloParams = [
            'idList' => $this->card->card_id,
            'name' => $ticketData['title'],
            'desc' => $ticketData['description'],
            'due' => $dueDate === '1970-01-01' ? null : $dueDate,
        ];

        $trelloTicket = $this->ticketService->create($trelloParams);
        $ticketId= $trelloTicket->id;
        $ticketData = [
            'ticket_id' => $ticketId,
            'category_id' => $categoryId,
            'assigned_id' => $ticketData['assignedId'] ?? null
        ];
        $ticket = $this->ticketService->make($ticketData);
        $this->agentTicketService->setAssigned($ticket);
        $this->agentTicketService->setAssigner($agent);
        $this->agentTicketService->assign();
        $this->personTicketService->setAssigned($ticket);
        $this->personTicketService->setAssigner($owner);
        $this->personTicketService->assign();
        $this->ticketService->save($ticket);

        return TicketResource::make($this->ticketService->getBatch([$ticket]));
    }

}
