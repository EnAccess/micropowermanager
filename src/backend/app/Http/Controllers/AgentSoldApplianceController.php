<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentSoldApplianceRequest;
use App\Http\Resources\ApiResource;
use App\Services\AgentService;
use App\Services\AgentSoldApplianceService;
use Illuminate\Http\Request;

class AgentSoldApplianceController extends Controller {
    public const FOR_APP = true;

    public function __construct(
        private AgentSoldApplianceService $agentSoldApplianceService,
        private AgentService $agentService,
    ) {}

    public function index(Request $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentSoldApplianceService->getAll($limit, $agent->id, null, self::FOR_APP));
    }

    public function show(int $customerId, Request $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentSoldApplianceService->getByCustomerId($agent->id, $customerId));
    }

    /**
     * Sell an assigned appliance to a customer.
     *
     * The down payment is recorded as a transaction and processed asynchronously;
     * `transaction_id` is what the field app polls the token for.
     */
    public function store(CreateAgentSoldApplianceRequest $request): ApiResource {
        $soldApplianceData = $request->only(['person_id', 'agent_assigned_appliance_id']);

        $soldAppliance = $this->agentSoldApplianceService->create($soldApplianceData);
        $transaction = $this->agentSoldApplianceService->processSaleFromRequest(
            $soldAppliance,
            $request->all(),
            $request->attributes->get('companyId'),
        );

        $soldAppliance->setAttribute('transaction_id', $transaction->id);

        return ApiResource::make($soldAppliance);
    }
}
