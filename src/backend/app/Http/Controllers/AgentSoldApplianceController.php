<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentSoldApplianceRequest;
use App\Http\Resources\ApiResource;
use App\Services\AgentService;
use App\Services\AgentSoldApplianceService;
use Illuminate\Http\Request;

class AgentSoldApplianceController extends Controller {
    public function __construct(
        private AgentSoldApplianceService $agentSoldApplianceService,
        private AgentService $agentService,
    ) {}

    public function index(Request $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $limit = $request->has('per_page') ? $request->integer('per_page') : null;

        return ApiResource::make($this->agentSoldApplianceService->list($agent->id, $limit));
    }

    public function show(int $customerId): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentSoldApplianceService->getByCustomerId($agent->id, $customerId));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateAgentSoldApplianceRequest $request): ApiResource {
        return ApiResource::make($this->agentSoldApplianceService->sell($request->all()));
    }
}
