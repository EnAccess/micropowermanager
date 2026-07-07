<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Transaction\Transaction;
use App\Services\AgentService;
use App\Services\AgentTransactionService;
use Illuminate\Http\Request;

class AgentTransactionsController extends Controller {
    public const FOR_APP = true;

    public function __construct(
        private AgentTransactionService $agentTransactionService,
        private AgentService $agentService,
    ) {}

    public function index(Request $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $limit = $request->input('per_page');

        return ApiResource::make($this->agentTransactionService->getAll($limit, $agent->id, self::FOR_APP));
    }

    public function show(int $customerId, Request $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();

        return ApiResource::make($this->agentTransactionService->getByCustomerId($agent->id, $customerId));
    }

    /**
     * Get the token of an agent transaction.
     *
     * Returns the token generated for one of the agent's transactions, if any.
     * Token generation is asynchronous (queued in ProcessPayment), so the
     * field app polls this endpoint after a successful POST until the token
     * appears or it gives up.
     */
    public function token(int $transactionId): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $transaction = $this->agentTransactionService->findForAgent($agent->id, $transactionId);

        if (!$transaction instanceof Transaction) {
            abort(404, 'Transaction not found.');
        }

        return ApiResource::make([
            'transaction_id' => $transaction->id,
            'token' => $transaction->token,
        ]);
    }
}
