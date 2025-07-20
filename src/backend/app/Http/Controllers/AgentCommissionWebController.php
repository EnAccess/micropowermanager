<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentCommissionRequest;
use App\Http\Resources\ApiResource;
use App\Services\AgentCommissionService;
use Illuminate\Http\Request;

class AgentCommissionWebController extends Controller {
    public function __construct(private AgentCommissionService $agentCommissionService) {}

    /**
     * Display a listing of the resource.
     *
     * @return ApiResource
     */
    public function index(Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentCommissionService->getAll($limit));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateAgentCommissionRequest $request
     *
     * @return ApiResource
     */
    public function store(CreateAgentCommissionRequest $request) {
        $commissionData = $request->only(['name', 'energy_commission', 'appliance_commission', 'risk_balance']);

        return ApiResource::make($this->agentCommissionService->create($commissionData));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param                              $agentCommissionId
     * @param CreateAgentCommissionRequest $request
     *
     * @return ApiResource
     */
    public function update(int $agentCommissionId, CreateAgentCommissionRequest $request): ApiResource {
        $agentCommission = $this->agentCommissionService->getById($agentCommissionId);

        return ApiResource::make($this->agentCommissionService->update($agentCommission, $request->all()));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $agentCommissionId
     *
     * @return ApiResource
     */
    public function destroy(int $agentCommissionId): ApiResource {
        $agentCommission = $this->agentCommissionService->getById($agentCommissionId);

        return ApiResource::make($this->agentCommissionService->delete($agentCommission));
    }
}
