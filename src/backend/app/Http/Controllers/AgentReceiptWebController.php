<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAgentReceiptRequest;
use App\Http\Resources\ApiResource;
use App\Models\AgentBalanceHistory;
use App\Services\AgentBalanceHistoryService;
use App\Services\AgentReceiptService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AgentReceiptWebController extends Controller {
    public function __construct(
        private AgentReceiptService $agentReceiptService,
        private AgentBalanceHistoryService $agentBalanceHistoryService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function show(int $agentId, Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentReceiptService->getAll($limit, $agentId));
    }

    /**
     * Display a listing of the resource.
     *
     * @return ApiResource
     */
    public function index(Request $request) {
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentReceiptService->getAll($limit));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return ApiResource
     */
    public function store(CreateAgentReceiptRequest $request) {
        $userId = auth('api')->user()->id;
        $agentId = $request->input('agent_id');
        $lastBalanceHistory = $this->agentBalanceHistoryService->getLastAgentBalanceHistory($agentId);

        if (!$lastBalanceHistory instanceof AgentBalanceHistory) {
            throw ValidationException::withMessages(['agent_id' => 'The agent has no recorded activity to create a receipt for.']);
        }

        $receiptData = [
            'user_id' => $userId,
            'agent_id' => $agentId,
            'amount' => $request->input('amount'),
            'last_controlled_balance_history_id' => $lastBalanceHistory->id,
        ];

        return ApiResource::make($this->agentReceiptService->create($receiptData));
    }
}
