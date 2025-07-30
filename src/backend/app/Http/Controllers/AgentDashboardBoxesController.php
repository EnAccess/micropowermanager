<?php

namespace App\Http\Controllers;

use App\Services\AgentBalanceHistoryService;
use App\Services\AgentReceiptService;
use App\Services\AgentService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AgentDashboardBoxesController extends Controller {
    public function __construct(
        private AgentService $agentService,
        private AgentBalanceHistoryService $agentBalanceHistoryService,
        private AgentReceiptService $agentReceiptService,
    ) {}

    public function show(Request $request, Response $response): Response {
        $agent = $this->agentService->getByAuthenticatedUser();
        $lastReceipt = $this->agentReceiptService->getLastReceipt($agent->id);
        $average = $this->agentBalanceHistoryService->getTransactionAverage($agent, $lastReceipt);
        $since = $this->agentReceiptService->getLastReceiptDate($agent);

        return $response->setStatusCode(200)->setContent(
            [
                'data' => [
                    'balance' => $agent->balance,
                    'profit' => $agent->commission_revenue,
                    'dept' => $agent->due_to_energy_supplier,
                    'average' => $average,
                    'since' => $since,
                    'status_code' => 200,
                ],
            ]
        );
    }
}
