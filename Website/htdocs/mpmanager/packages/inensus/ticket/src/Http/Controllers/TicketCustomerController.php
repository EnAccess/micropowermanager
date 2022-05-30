<?php

namespace Inensus\Ticket\Http\Controllers;

use App\Services\PersonService;
use App\Services\PersonTicketService;
use App\Services\UserTicketService;
use Illuminate\Http\Request;
use Inensus\Ticket\Exceptions\TicketOwnerNotFoundException;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Services\TicketBoardService;
use Inensus\Ticket\Services\TicketCardService;
use Inensus\Ticket\Services\TicketCategoryService;
use Inensus\Ticket\Services\TicketOutSourceService;
use Inensus\Ticket\Services\TicketService;
use Inensus\Ticket\Services\TicketUserService;

class TicketCustomerController extends Controller
{

    private $board;
    private $card;

    public function __construct(
        private TicketBoardService $boardService,
        private TicketCardService $cardService,
        private TicketService $ticketService,
        private UserTicketService $userTicketService,
        private PersonTicketService $personTicketService,
        private TicketCategoryService $ticketCategoryService,
        private PersonService $personService,
        private TicketOutSourceService $ticketOutSourceService,
        private TicketUserService $ticketUserService

    ) {

    }

    public function store(Request $request): TicketResource
    {
        $this->board = $this->boardService->initializeBoard($this->ticketUserService);
        $this->card = $this->cardService->initalizeList($this->board);

        $ticketData = $request->only([
            'owner_id',
            'dueDate',
            'label',
            'title',
            'description',
            'assignedPerson',
            'outsourcing'
        ]);
        $ownerId = $ticketData['owner_id'];
        $owner = $this->personService->getById($ownerId);

        if (!$owner) {
            throw new TicketOwnerNotFoundException('Ticket owner with following id not found ' . $ownerId);
        }

        //reformat due date if it is set
        $dueDate = isset($ticketData['dueDate']) ? date('Y-m-d H:i:00', strtotime($ticketData['dueDate'])) : null;
        $categoryId = $ticketData['label'];
        $trelloParams = [
            'idList' => $this->card->card_id,
            'name' => $request->get('title'),
            'desc' => $request->get('description'),
            'due' => $dueDate === '1970-01-01' ? null : $dueDate,
            'idMembers' => $ticketData['assignedPerson'],

        ];
        $trelloTicket = $this->ticketService->create($trelloParams);
        $ticketId = $trelloTicket->id;
        $ticketData = [
            'ticket_id' => $ticketId,
            'category_id' => $categoryId,
            'assigned_id' => $ticketData['assignedPerson'] ?? null
        ];

        $user = auth('api')->user();
        $ticket = $this->ticketService->make($ticketData);
        $this->userTicketService->setAssigned($ticket);
        $this->userTicketService->setAssigner($user);
        $this->userTicketService->assign();
        $this->personTicketService->setAssigned($ticket);
        $this->personTicketService->setAssigner($owner);
        $this->personTicketService->assign();
        $this->ticketService->save($ticket);
        //get category to check outsourcing
        $categoryData = $this->ticketCategoryService->getById($categoryId);

        if ($categoryData->out_source) {
            $ticketOutsourceData = [
                'ticket_id' => $ticket->id,
                'amount' => (int)$request->get('outsourcing')
            ];
            $this->ticketOutSourceService->create($ticketOutsourceData);
        }

        return TicketResource::make($this->ticketService->getBatch([$ticket]));
    }

    public function index($customerId, Request $request)
    {

        $limit = 5;
        $agentId = null;
        $status = null;

        return TicketResource::make($this->ticketService->getAll($limit, $status, $agentId, $customerId));

    }
}