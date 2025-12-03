<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketResource;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller {
    public const FOR_APP = false;

    public function __construct(private TicketService $ticketService) {}

    public function index(Request $request): TicketResource {
        $assignedId = $request->input('person');
        $categoryId = $request->input('category');
        $status = $request->input('status');
        $limit = $request->input('limit', 5);
        $agentId = $request->input('agent');
        $customerId = $request->input('customer');

        return TicketResource::make($this->ticketService->getAll(
            $limit,
            $status,
            $agentId,
            $customerId,
            $assignedId,
            $categoryId
        ));
    }

    public function show(int $id): TicketResource {
        $ticket = $this->ticketService->getById($id);

        return TicketResource::make(collect($ticket));
    }

    // TODO: change this on UI side with query parameter $ticketId
    public function destroy(int $ticketId, Request $request): TicketResource {
        $closed = $this->ticketService->close($ticketId);

        return TicketResource::make(['data' => $closed]);
    }
}
