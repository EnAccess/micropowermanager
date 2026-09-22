<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentSoldApplianceRequest;
use App\Http\Resources\ApiResource;
use App\Jobs\ProcessPayment;
use App\Models\Transaction\Transaction;
use App\Services\AgentSoldApplianceService;
use Illuminate\Http\Request;

class AgentSoldApplianceWebController extends Controller {
    public function __construct(private AgentSoldApplianceService $agentSoldApplianceService) {}

    public function index(int $agentId, Request $request): ApiResource {
        $limit = $request->integer('per_page') ?: null;

        return ApiResource::make($this->agentSoldApplianceService->list($agentId, $limit));
    }

    /**
     * Record an appliance sale on behalf of an agent.
     *
     * Books the sale exactly like an agent-app sale: the appliance is taken from the stock
     * assigned to the agent and the resulting sold appliance is attributed to that agent.
     */
    public function store(CreateAgentSoldApplianceRequest $request): ApiResource {
        $result = $this->agentSoldApplianceService->sell($request->all());

        if ($result['process_immediately'] && $result['transaction'] instanceof Transaction) {
            dispatch(new ProcessPayment($request->attributes->get('companyId'), $result['transaction']->id));
        }

        return ApiResource::make($result['sold_appliance']);
    }
}
