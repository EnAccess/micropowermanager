<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\AgentTransactionService;
use Illuminate\Http\Request;

class AgentTransactionWebController extends Controller {
    public function __construct(private AgentTransactionService $agentTransactionService) {}

    public function index(int $agentId, Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentTransactionService->getAll($limit, $agentId));
    }
}
