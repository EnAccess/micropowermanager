<?php

namespace App\Http\Controllers;

use App\Services\AgentCustomersPaymentHistoryService;
use App\Services\AgentService;

class AgentCustomersPaymentHistoryController extends Controller {
    public function __construct(
        private AgentService $agentService,
        private AgentCustomersPaymentHistoryService $agentCustomersPaymentHistoryService,
    ) {}

    /**
     * @return array<mixed>
     */
    public function show(int $customerId, string $period, ?int $limit = null, string $order = 'ASC'): array {
        return $this->agentCustomersPaymentHistoryService->getPaymentFlowByCustomerId(
            $period,
            $customerId,
            $limit,
            $order
        );
    }

    /**
     * @return array<mixed>
     */
    public function index(string $period, ?int $limit = null, string $order = 'ASC'): array {
        $agent = $this->agentService->getByAuthenticatedUser();

        return $this->agentCustomersPaymentHistoryService->getPaymentFlows($period, $agent->id, $limit, $order);
    }
}
