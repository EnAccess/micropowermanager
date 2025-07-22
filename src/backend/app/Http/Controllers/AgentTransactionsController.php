<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\AgentService;
use App\Services\AgentTransactionService;
use Illuminate\Http\Request;

class AgentTransactionsController extends Controller {
    public const FOR_APP = true;

    public function __construct(
        private AgentTransactionService $agentTransactionService,
        private AgentService $agentService,
    ) {}

    public function index(Request $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentTransactionService->getAll($limit, $agent->id, self::FOR_APP));
    }

    public function show(int $customerId, Request $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentTransactionService->getByCustomerId($agent->id, $customerId));
    }
}
