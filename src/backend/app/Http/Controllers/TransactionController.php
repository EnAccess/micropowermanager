<?php

namespace App\Http\Controllers;

use App\Enums\PaymentInitiationProvider;
use App\Events\TransactionSavedEvent;
use App\Http\Resources\ApiResource;
use App\Jobs\ProcessPayment;
use App\Providers\Interfaces\ITransactionProvider;
use App\Services\AgentService;
use App\Services\AgentTransactionService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller {
    public function __construct(
        private TransactionService $transactionService,
        private AgentService $agentService,
        private AgentTransactionService $agentTransactionService,
    ) {}

    public function index(): ApiResource {
        $limit = \request()->input('per_page') ?? 15;

        return ApiResource::make($this->transactionService->getAll($limit));
    }

    public function show(int $id): ApiResource {
        $transaction = $this->transactionService->getById($id);

        return ApiResource::make($transaction);
    }

    public function store(Request $request): ApiResource {
        $provider = PaymentInitiationProvider::fromRequestValue($request->input('payment_provider'));

        if ($provider !== PaymentInitiationProvider::Cash) {
            return $this->storeProviderTransaction($request);
        }

        /**
         * @var ITransactionProvider $transactionProvider
         */
        $transactionProvider = $request->attributes->get('transactionProcessor');
        $transactionProvider->saveTransaction();
        $transaction = $transactionProvider->saveCommonData();
        event(new TransactionSavedEvent($transactionProvider));

        if (isset($transaction->id)) {
            $companyId = $request->attributes->get('companyId') ?? null;
            if ($companyId !== null) {
                dispatch(new ProcessPayment($companyId, $transaction->id));
            } else {
                Log::warning('Company ID not found in request attributes. Payment transaction job not triggered for transaction '.$transaction->id);
            }
        }

        return ApiResource::make([
            'id' => $transaction->id ?? null,
        ]);
    }

    /**
     * A top-up the customer pays through a payment provider rather than handing cash to the agent.
     * The agent's balance is untouched — they never hold the money — so this deliberately skips
     * AgentTransactionProvider and the AgentTransaction it would create.
     */
    private function storeProviderTransaction(Request $request): ApiResource {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'device_serial' => ['required', 'string'],
            'payment_provider' => ['required', PaymentInitiationProvider::validationRule()],
            'payer_phone' => ['sometimes', 'string', 'phone:INTERNATIONAL'],
        ]);

        $agent = $this->agentService->getByAuthenticatedUser();

        try {
            DB::connection('tenant')->beginTransaction();

            $result = $this->agentTransactionService->initiateProviderPayment(
                $agent,
                $request->string('device_serial')->toString(),
                $request->float('amount'),
                PaymentInitiationProvider::fromRequestValue($request->input('payment_provider')),
                $request->has('payer_phone') ? $request->string('payer_phone')->toString() : null,
            );

            DB::connection('tenant')->commit();
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }

        $transaction = $result['transaction'];

        if ($result['process_immediately']) {
            dispatch(new ProcessPayment($request->attributes->get('companyId'), $transaction->id));
        }

        return ApiResource::make(array_merge(['id' => $transaction->id], $result['provider_data']));
    }
}
