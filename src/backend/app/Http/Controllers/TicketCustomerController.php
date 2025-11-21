<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserTicketCreateRequest;
use App\Http\Resources\TicketResource;
use App\Services\PersonService;
use App\Services\PersonTicketService;
use App\Services\TicketCategoryService;
use App\Services\TicketOutSourceService;
use App\Services\TicketService;
use App\Services\UserTicketService;
use Illuminate\Http\Request;

class TicketCustomerController extends Controller {
    public function __construct(
        private TicketService $ticketService,
        private UserTicketService $userTicketService,
        private PersonTicketService $personTicketService,
        private TicketCategoryService $ticketCategoryService,
        private PersonService $personService,
        private TicketOutSourceService $ticketOutSourceService,
    ) {}

    public function store(UserTicketCreateRequest $request): TicketResource {
        $ticketData = $request->getMappedArray();
        $user = auth('api')->user();
        $ticketData['status'] = 0;
        $ticket = $this->ticketService->make($ticketData);
        $this->userTicketService->setAssigned($ticket);
        $this->userTicketService->setAssignee($user);
        $this->userTicketService->assign();

        $owner = $this->personService->getById($request->getOwnerId());
        $this->personTicketService->setAssigned($ticket);
        $this->personTicketService->setAssignee($owner);
        $this->personTicketService->assign();

        $this->ticketService->save($ticket);
        // get category to check outsourcing
        $categoryData = $this->ticketCategoryService->getById($request->getLabel());
        if ($categoryData->out_source) {
            $ticketOutsourceData = [
                'ticket_id' => $ticket->id,
                'amount' => (int) $request->get('outsourcing'),
            ];
            $this->ticketOutSourceService->create($ticketOutsourceData);
        }

        return TicketResource::make($ticket);
    }

    public function index(int $customerId, Request $request): TicketResource {
        $limit = 5;
        $agentId = null;
        $status = null;

        return TicketResource::make($this->ticketService->getAll($limit, $status, $agentId, $customerId));
    }
}
