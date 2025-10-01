<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentBalanceHistoryRequest;
use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Services\AgentBalanceHistoryService;
use Illuminate\Http\Request;

class AgentBalanceHistoryController extends Controller {
    private AgentBalanceHistoryService $agentBalanceHistoryService;

    public function __construct(AgentBalanceHistoryService $agentBalanceHistoryService) {
        $this->agentBalanceHistoryService = $agentBalanceHistoryService;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Agent $agent, CreateAgentBalanceHistoryRequest $request): ApiResource {
        $agentBalanceHistory = $this->agentBalanceHistoryService->create(
            array_merge(
                ['agent_id' => $agent->id],
                $request->only(['amount'])
            )
        );

        return new ApiResource($agentBalanceHistory);
    }

    /**
     * Display the specified resource.
     *
     * @return void
     */
    public function show(AgentBalanceHistory $agent_balance_history) {}

    /**
     * Update the specified resource in storage.
     *
     * @return void
     */
    public function update(Request $request, AgentBalanceHistory $agent_balance_history) {}

    /**
     * Remove the specified resource from storage.
     *
     * @return void
     */
    public function destroy(AgentBalanceHistory $agent_balance_history) {}
}
