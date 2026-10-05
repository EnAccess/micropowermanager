<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserTicketCreateRequest;
use App\Http\Resources\TicketResource;
use App\Services\AgentService;
use App\Services\PersonService;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketAgentController extends Controller {
    public function __construct(
        private TicketService $ticketService,
        private AgentService $agentService,
        private PersonService $personService,
    ) {}

    public function index(int $agentId, Request $request): TicketResource {
        $limit = $request->integer('per_page', 15);
        $status = $request->filled('status') ? $request->integer('status') : null;

        return TicketResource::make($this->ticketService->getAll($limit, $status, $agentId));
    }

    public function store(int $agentId, UserTicketCreateRequest $request): TicketResource {
        $ticket = $this->ticketService->create(
            title: $request->getTitle(),
            content: $request->getDescription(),
            categoryId: $request->getLabel(),
            assignedId: $request->getAssignedPerson(),
            dueDate: $request->getDueDate(),
            owner: $this->personService->getById($request->getOwnerId()),
            creator: $this->agentService->getById($agentId),
        );

        return TicketResource::make($ticket);
    }
}
