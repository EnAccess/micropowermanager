<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentChargeRequest;
use App\Http\Resources\ApiResource;
use App\Services\AgentChargeService;

class AgentChargeWebController extends Controller {
    public function __construct(
        private AgentChargeService $agentChargeService,
    ) {}

    public function store(CreateAgentChargeRequest $request): ApiResource {
        $userId = auth('api')->user()->id;

        $agentChargeData = [
            'agent_id' => $request->input('agent_id'),
            'amount' => $request->input('amount'),
            'user_id' => $userId,
        ];

        return ApiResource::make($this->agentChargeService->create($agentChargeData));
    }
}
