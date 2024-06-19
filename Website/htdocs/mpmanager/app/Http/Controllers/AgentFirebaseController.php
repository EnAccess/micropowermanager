<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\AgentService;
use Illuminate\Http\Request;

class AgentFirebaseController extends Controller
{
    public function __construct(
        private AgentService $agentService
    ) {
    }

    public function update(Request $request): ApiResource
    {
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentService->setFirebaseToken($agent, $request->input('fire_base_token')));
    }
}
