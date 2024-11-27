<?php

namespace Inensus\Ticket\Http\Controllers;

use Illuminate\Http\Request;
use Inensus\Ticket\Http\Resources\TicketResource;
use Inensus\Ticket\Services\TicketService;

class TicketAgentController extends Controller {
    public function __construct(private TicketService $ticketService) {}

    public function index($agentId, Request $request): TicketResource {
        $limit = 5;
        $status = null;

        return TicketResource::make($this->ticketService->getAll($limit, $status, $agentId));
    }
}
