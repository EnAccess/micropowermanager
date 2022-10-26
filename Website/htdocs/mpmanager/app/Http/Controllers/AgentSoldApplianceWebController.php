<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Services\AgentSoldApplianceService;
use Illuminate\Http\Request;

class AgentSoldApplianceWebController extends Controller
{
    public function __construct(private AgentSoldApplianceService $agentSoldApplianceService)
    {
    }

    public function index($agentId, Request $request): ApiResource
    {
        $limit = $request->input('limit');

        return ApiResource::make($this->agentSoldApplianceService->getAll($limit, $agentId));
    }
}
