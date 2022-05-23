<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Models\Transaction\AgentTransaction;
use App\Services\AgentTransactionService;
use Illuminate\Http\Request;

/**
 * @group   AgentTransactions
 * Class AgentTransactionController
 * @package App\Http\Controllers
 */
class AgentTransactionsController extends Controller
{
    private $agentTransactionService;


    public function __construct(AgentTransactionService $agentTransactionService)
    {
        $this->agentTransactionService = $agentTransactionService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return ApiResource
     */
    public function index(): ApiResource
    {
        $agent = request()->attributes->get('user');
        $transactions = $this->agentTransactionService->list($agent->id);
        return new ApiResource($transactions);
    }
    public function agentCustomerTransactions($customerId, Request $request): ApiResource
    {
        $agent = request()->attributes->get('user');
        $transactions = $this->agentTransactionService->listByCustomer($agent->id, $customerId);
        return new ApiResource($transactions);
    }
    public function show($customerId): ApiResource
    {
        $agent = request()->attributes->get('user');
        $transactions = $this->agentTransactionService->listByCustomer($agent->id, $customerId);
        return new ApiResource($transactions);
    }

}
