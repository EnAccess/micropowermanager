<?php

namespace App\Http\Controllers;

use App\Services\AgentBalanceHistoryService;
use App\Services\AgentReceiptService;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;

class AgentDashboardBoxesController extends Controller {
    public function __construct(
        private AgentService $agentService,
        private AgentBalanceHistoryService $agentBalanceHistoryService,
        private AgentReceiptService $agentReceiptService,
    ) {}

    public function show(): JsonResponse {
        $agent = $this->agentService->getByAuthenticatedUser();
        $lastReceipt = $this->agentReceiptService->getLastReceipt($agent->id);
        $average = $this->agentBalanceHistoryService->getTransactionAverage($agent, $lastReceipt);
        $since = $this->agentReceiptService->getLastReceiptDate($agent);

        return response()->json([
            'data' => [
                'balance' => $agent->balance,
                'profit' => $agent->commission_revenue,
                'dept' => $agent->due_to_energy_supplier,
                'average' => $average,
                'since' => $since,
                'status_code' => 200,
            ],
        ], 200);
    }
}
