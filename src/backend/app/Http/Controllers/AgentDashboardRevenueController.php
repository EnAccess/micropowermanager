<?php

namespace App\Http\Controllers;

use App\Services\AgentBalanceHistoryService;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgentDashboardRevenueController extends Controller {
    public function __construct(
        private AgentService $agentService,
        private AgentBalanceHistoryService $agentBalanceHistoryService,
    ) {}

    public function show(Request $request, Response $response): array {
        $agent = $this->agentService->getByAuthenticatedUser();

        return $this->agentBalanceHistoryService->getAgentRevenuesWeekly($agent);
    }
}
