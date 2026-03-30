<?php

namespace App\Http\Controllers;

use App\Http\Resources\TicketResource;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketAgentController extends Controller {
    public function __construct(private TicketService $ticketService) {}

    public function index(int $agentId, Request $request): TicketResource {
        $limit = 5;
        $status = null;

        return TicketResource::make($this->ticketService->getAll($limit, $status, $agentId));
    }
}
