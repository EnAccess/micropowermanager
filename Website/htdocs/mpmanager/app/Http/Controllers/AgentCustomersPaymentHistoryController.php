<?php

namespace App\Http\Controllers;

use App\Models\PaymentHistory;
use App\Services\AgentCustomersPaymentHistoryService;
use App\Services\AgentService;
use Illuminate\Http\Request;

class AgentCustomersPaymentHistoryController extends Controller
{
     public function __construct(
         private AgentService $agentService,
         private AgentCustomersPaymentHistoryService $agentCustomersPaymentHistoryService
     ) {

     }

    public function show(int $customerId, string $period, $limit = null, $order = 'ASC')
    {
        return $this->agentCustomersPaymentHistoryService->getPaymentFlowByCustomerId($period,$customerId, $limit,
            $order);
    }


    public function index(string $period, $limit = null, $order = 'ASC')
    {
        $agent = $this->agentService->getByAuthenticatedUser();

       return $this->agentCustomersPaymentHistoryService->getPaymentFlows($period, $agent->id, $limit, $order);
    }


}
