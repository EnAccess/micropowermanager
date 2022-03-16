<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentBalanceHistoryRequest;
use App\Http\Resources\ApiResource;
use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Models\User;
use App\Services\AgentBalanceHistoryService;
use Illuminate\Http\Request;

class AgentBalanceHistoryController extends Controller
{

    private $agentBalanceHistoryService;

    public function __construct(AgentBalanceHistoryService $agentBalanceHistoryService)
    {
        $this->agentBalanceHistoryService = $agentBalanceHistoryService;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Agent                            $agent
     * @param  CreateAgentBalanceHistoryRequest $request
     * @return ApiResource
     */
    public function store(Agent $agent, CreateAgentBalanceHistoryRequest $request): ApiResource
    {
        $agentBalanceHistory = $this->agentBalanceHistoryService->create(
            $agent,
            $request->only(
                [
                'amount'
                ]
            )
        );

        return new ApiResource($agentBalanceHistory);
    }


    /**
     * Display the specified resource.
     *
     * @param  AgentBalanceHistory $agent_balance_history
     * @return void
     */
    public function show(agentBalanceHistory $agent_balance_history)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  AgentBalanceHistory      $agent_balance_history
     * @return void
     */
    public function update(Request $request, agentBalanceHistory $agent_balance_history)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  AgentBalanceHistory $agent_balance_history
     * @return void
     */
    public function destroy(agentBalanceHistory $agent_balance_history)
    {
        //
    }


    /**
     * Display a listing of the resource.
     *
     * @param  Agent   $agent
     * @param  Request $request
     * @return ApiResource
     */
    public function indexWeb(Agent $agent, Request $request)
    {

        $balanceHistories = $this->agentBalanceHistoryService->agentBalanceHistories($agent->id);
        return new ApiResource($balanceHistories);
    }
}
