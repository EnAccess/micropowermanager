<?php

namespace App\Http\Controllers;

use App\Enums\PaymentInitiationProvider;
use App\Http\Requests\AgentAppliancePaymentRequest;
use App\Http\Requests\CreateAgentSoldApplianceRequest;
use App\Http\Resources\ApiResource;
use App\Jobs\ProcessPayment;
use App\Models\Agent;
use App\Models\AppliancePerson;
use App\Models\Transaction\Transaction;
use App\Services\AgentService;
use App\Services\AgentSoldApplianceService;
use App\Services\AgentTransactionService;
use App\Services\AppliancePaymentService;
use App\Services\AppliancePersonService;
use App\Services\TransactionService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

#[Group('AgentApp', weight: 21)]
class AgentSoldApplianceController extends Controller {
    public const FOR_APP = true;

    public function __construct(
        private AgentSoldApplianceService $agentSoldApplianceService,
        private AgentService $agentService,
        private AgentTransactionService $agentTransactionService,
        private AppliancePaymentService $appliancePaymentService,
        private AppliancePersonService $appliancePersonService,
        private TransactionService $transactionService,
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
     * Sell an appliance to a customer.
     *
     * `payment_provider` is one of the IDs returned by the payment providers endpoint;
     * omit it (or send `0`) to record the down payment as cash the agent collected.
     * A provider payment that the provider rejects rolls the whole sale back.
     */
    public function store(CreateAgentSoldApplianceRequest $request): JsonResponse {
        $soldApplianceData = $request->only(['person_id', 'agent_assigned_appliance_id']);

        try {
            DB::connection('tenant')->beginTransaction();

            $soldAppliance = $this->agentSoldApplianceService->create($soldApplianceData);
            $result = $this->agentSoldApplianceService->processSaleFromRequest($soldAppliance, $request->all());

            DB::connection('tenant')->commit();
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }

        $this->dispatchWhenImmediate($request, $result);

        return ApiResource::make(array_merge(
            [
                'appliance_person' => $soldAppliance,
                'transaction_id' => $result['transaction']?->id,
            ],
            $result['provider_data'],
        ))->response()->setStatusCode(201);
    }

    /**
     * Pay an installment of an appliance sold to a customer.
     *
     * `payment_provider` is one of the IDs returned by the payment providers endpoint;
     * omit it (or send `0`) to record cash the agent collected. Poll the transaction status
     * endpoint with the returned `transaction_id` for the outcome of a provider payment.
     */
    public function storePayment(int $appliancePersonId, AgentAppliancePaymentRequest $request): ApiResource {
        $agent = $this->agentService->getByAuthenticatedUser();
        $appliancePerson = $this->agentSoldApplianceService->findForAgent($agent, $appliancePersonId);
        $providerId = $request->integer('payment_provider');
        $amount = $request->float('amount');

        try {
            DB::connection('tenant')->beginTransaction();

            $applianceDetail = $this->appliancePersonService->getSoldApplianceDetails($appliancePerson->id);

            $result = $providerId === PaymentInitiationProvider::Cash->value
                ? $this->collectCashInstallment($agent, $applianceDetail, $amount)
                : $this->appliancePaymentService->initiateInstallmentPayment(
                    $applianceDetail,
                    $amount,
                    $providerId,
                    $request->has('payer_phone') ? $request->string('payer_phone')->toString() : null,
                    $agent->id,
                );

            DB::connection('tenant')->commit();
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }

        $this->dispatchWhenImmediate($request, $result);

        return ApiResource::make(array_merge(
            ['transaction_id' => $result['transaction']->id],
            $result['provider_data'],
        ));
    }

    /**
     * Cash an agent collected is money they now hold, so it becomes an AgentTransaction and the
     * agent's balance is credited once the payment is processed. Routing it through
     * CashTransactionService instead would file it against a user id the agent does not have.
     *
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
     */
    private function collectCashInstallment(Agent $agent, AppliancePerson $applianceDetail, float $amount): array {
        $this->appliancePaymentService->validateAmount($applianceDetail, $amount);

        $agentTransaction = $this->agentTransactionService->create([
            'agent_id' => $agent->id,
            'mobile_device_id' => $agent->mobile_device_id,
            'status' => 1,
        ]);

        $transaction = $this->transactionService->make([
            'amount' => $amount,
            'sender' => 'Agent-'.$agent->id,
            'message' => $applianceDetail->device_serial ?? (string) $applianceDetail->id,
            'type' => Transaction::TYPE_DEFERRED_PAYMENT,
        ]);
        $transaction->originalTransaction()->associate($agentTransaction);
        $this->transactionService->save($transaction);

        return ['transaction' => $transaction, 'provider_data' => [], 'process_immediately' => true];
    }

    /**
     * @param array{transaction: Transaction|null, provider_data: array<string, mixed>, process_immediately: bool} $result
     */
    private function dispatchWhenImmediate(Request $request, array $result): void {
        if (!$result['process_immediately'] || $result['transaction'] === null) {
            return;
        }

        dispatch(new ProcessPayment($request->attributes->get('companyId'), $result['transaction']->id));
    }
}
