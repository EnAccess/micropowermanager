<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\AgentSoldApplianceService;
use Illuminate\Http\Request;

class AgentSoldApplianceWebController extends Controller {
    public function __construct(private AgentSoldApplianceService $agentSoldApplianceService) {}

    public function index(int $agentId, Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentSoldApplianceService->getAll($limit, $agentId));
    }
}
