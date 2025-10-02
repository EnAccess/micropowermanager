<?php

namespace Inensus\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Services\TicketService;

class TicketController extends Controller {
    public const FOR_APP = false;

    public function __construct(private TicketService $ticketService) {}

    public function index(Request $request): TicketResource {
        $assignedId = $request->input('person') ?? null;
        $categoryId = $request->input('category') ?? null;
        $status = $request->input('status') ?? null;
        $limit = $request->input('limit') ?? 5;
        $agentId = $request->input('agent') ?? null;
        $customerId = $request->input('customer') ?? null;

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
    public function destroy(int $ticketId, Request $request) {
        $closed = $this->ticketService->close($ticketId);

        return TicketResource::make(['data' => $closed]);
    }
}
