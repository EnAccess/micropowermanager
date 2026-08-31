<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Http\Resources\PaymentProviderResource;
use App\Http\Resources\PaymentStatusResource;
use App\Models\Transaction\Transaction;
use App\Services\AgentService;
use App\Services\AgentTransactionService;
use App\Services\AppliancePaymentService;
use App\Services\PaymentInitiationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

#[Group('AgentApp', weight: 21)]
class AgentTransactionsController extends Controller {
    public const FOR_APP = true;

    public function __construct(
        private AgentTransactionService $agentTransactionService,
        private AgentService $agentService,
        private AppliancePaymentService $appliancePaymentService,
        private PaymentInitiationService $paymentInitiationService,
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
     * List the payment providers the agent may collect through.
     *
     * Returns the payment provider plugins that are _both_ enabled for the tenant and able to
     * initiate a payment. Use a provider's `id` as the `payment_provider` value when recording a
     * transaction, selling an appliance or paying an installment.
     * Cash (ID `0`) is always available and is not part of this list.
     */
    public function paymentProviders(): AnonymousResourceCollection {
        return PaymentProviderResource::collection($this->paymentInitiationService->paymentProviders());
    }

    /**
     * Check the status of a payment the agent initiated.
     *
     * Poll this with the `transaction_id` returned when initiating a payment. `processed` flips to
     * true once the payment has been applied; `failed` is terminal and means the provider rejected
     * it, so stop polling.
     */
    public function status(int $transactionId): PaymentStatusResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $transaction = $this->agentTransactionService->findForAgent($agent, $transactionId);

        if (!$transaction instanceof Transaction) {
            abort(404, 'Transaction not found.');
        }

        return PaymentStatusResource::make($this->appliancePaymentService->checkPaymentStatus($transaction));
    }

    /**
     * Get the token of a transaction the agent may read.
     *
     * Returns the token generated for the transaction, if any. Token generation
     * is asynchronous (queued in ProcessPayment), so the field app polls this
     * endpoint after a successful POST until the token appears or it gives up.
     */
    public function token(int $transactionId): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $transaction = $this->agentTransactionService->findForAgent($agent, $transactionId);

        if (!$transaction instanceof Transaction) {
            abort(404, 'Transaction not found.');
        }

        return ApiResource::make([
            'transaction_id' => $transaction->id,
            'token' => $transaction->token,
        ]);
    }
}
