<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentTicketRequest;
use App\Http\Resources\ApiResource;
use App\Services\AgentService;
use App\Services\AgentTicketService;
use App\Services\PersonService;
use App\Services\PersonTicketService;
use Illuminate\Http\Request;
use Inensus\Ticket\Exceptions\TicketOwnerNotFoundException;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Services\TicketService;

class AgentTicketController extends Controller {
    public function __construct(
        private AgentTicketService $agentTicketService,
        private PersonTicketService $personTicketService,
        private AgentService $agentService,
        private TicketService $ticketService,
        private PersonService $personService,
    ) {}

    public function index(Request $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->ticketService->getForAgent($agent->id));
    }

    public function show(int $ticketId, Request $request): ApiResource {
        return ApiResource::make($this->ticketService->getById($ticketId));
    }

    public function store(CreateAgentTicketRequest $request): TicketResource {
        $ticketData = $request->only([
            'owner_id',
            'due_date',
            'label',
            'title',
            'description',
            'assignedId',
        ]);
        $ownerId = $ticketData['owner_id'];

        try {
            $owner = $this->personService->getById($ownerId);
        } catch (TicketOwnerNotFoundException $e) {
            throw new TicketOwnerNotFoundException('Ticket owner with following id not found '.$ownerId);
        }

        $agent = $this->agentService->getByAuthenticatedUser();
        // reformat due date if it is set
        $dueDate = isset($ticketData['due_date']) ? date('Y-m-d H:i:00', strtotime($ticketData['due_date'])) : null;

        $ticketData = [
            'title' => $ticketData['title'],
            'content' => $ticketData['description'],
            'due_date' => $dueDate === '1970-01-01' ? null : $dueDate,
            'status' => $ticketData['status'] ?? 0,
            'category_id' => $ticketData['label'],
            'assigned_id' => $ticketData['assignedId'] ?? null,
        ];
        $ticket = $this->ticketService->make($ticketData);
        $this->agentTicketService->setAssigned($ticket);
        $this->agentTicketService->setAssignee($agent);
        $this->agentTicketService->assign();
        $this->personTicketService->setAssigned($ticket);
        $this->personTicketService->setAssignee($owner);
        $this->personTicketService->assign();
        $this->ticketService->save($ticket);

        return TicketResource::make($this->ticketService->getBatch([$ticket]));
    }
}
