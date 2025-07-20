<?php

namespace App\Http\Controllers;

use App\Services\AgentBalanceHistoryService;
use App\Services\AgentReceiptService;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgentDashboardBalanceHistoryController extends Controller {
    public function __construct(
        private AgentService $agentService,
        private AgentBalanceHistoryService $agentBalanceHistoryService,
        private AgentReceiptService $agentReceiptService,
    ) {}

    public function show(Request $request, Response $response): array {
        $agent = $this->agentService->getByAuthenticatedUser();
        $lastReceiptDate = $this->agentReceiptService->getLastReceiptDate($agent);

        return $this->agentBalanceHistoryService->getGraphValues($agent, $lastReceiptDate);
    }
}
