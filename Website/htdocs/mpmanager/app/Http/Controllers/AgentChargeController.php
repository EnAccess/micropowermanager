<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentChargeRequest;
use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Services\AgentChargeService;
use Illuminate\Http\Request;

class AgentChargeController extends Controller
{
    private $agentChargeService;

    public function __construct(AgentChargeService $agentChargeService)
    {
        $this->agentChargeService = $agentChargeService;
    }

    public function store(Agent $agent, CreateAgentChargeRequest $request): ApiResource
    {
        $agentCharge = $this->agentChargeService->create($agent, $request->only(['user_id']));
        return new ApiResource($agentCharge);
    }
}
