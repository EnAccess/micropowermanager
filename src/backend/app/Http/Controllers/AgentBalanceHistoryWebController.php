<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\AgentBalanceHistoryService;
use Illuminate\Http\Request;

class AgentBalanceHistoryWebController extends Controller {
    public function __construct(private AgentBalanceHistoryService $agentBalanceHistoryService) {}

    public function index(int $agentId, Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentBalanceHistoryService->getAll($limit, $agentId));
    }
}
