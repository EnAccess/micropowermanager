<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inensus\PaystackPaymentProvider\Http\Requests\TransactionInitializeRequest;
use Inensus\PaystackPaymentProvider\Http\Resources\PaystackResource;
use Inensus\PaystackPaymentProvider\Http\Resources\PaystackTransactionResource;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\PaystackPaymentProvider\Modules\Api\PaystackApiService;
use Inensus\PaystackPaymentProvider\Services\PaystackTransactionService;
use Inensus\PaystackPaymentProvider\Services\PaystackWebhookService;

class PaystackController extends Controller {
    public function __construct(
        private PaystackTransactionService $transactionService,
        private PaystackApiService $apiService,
        private PaystackWebhookService $webhookService,
    ) {}

    public function startTransaction(TransactionInitializeRequest $request): PaystackResource {
        $transaction = $request->getPaystackTransaction();

        return PaystackResource::make($this->apiService->initializeTransaction($transaction));
    }

    /**
     * @return JsonResponse
     */
    public function webhookCallback(Request $request, int $companyId) {
        try {
            // Verify webhook signature
            if (!$this->webhookService->verifyWebhook($request)) {
                return response()->json(['error' => 'Invalid webhook signature'], 401);
            }
            Log::info('PaystackWebhookService: Request', ['request' => $request->all()]);
            // Process the webhook
            $this->webhookService->processWebhook($request, $companyId);
        } catch (\Exception $e) {
            Log::info('PaystackWebhookService: Failed to verify webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * @return JsonResponse
     */
    public function verifyTransaction(Request $request, string $reference) {
        $result = $this->apiService->verifyTransaction($reference);

        if ($result['error']) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json($result);
    }

    /**
     * @return JsonResponse
     */
    public function getTransactions(Request $request) {
        $perPage = (int) $request->input('per_page', 15);
        $transactions = $this->transactionService->getAll($perPage);

        return response()->json($transactions);
    }

    /**
     * @return PaystackTransactionResource|JsonResponse
     */
    public function getTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction instanceof PaystackTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        return PaystackTransactionResource::make($transaction);
    }

    /**
     * @return PaystackTransactionResource|JsonResponse
     */
    public function updateTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction instanceof PaystackTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $request->validate([
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'in:NGN,USD,GHS,KES,ZAR'],
            'status' => ['sometimes', 'integer', 'in:0,1,2,3'],
        ]);

        $updatedTransaction = $this->transactionService->update($transaction, $request->only([
            'amount', 'currency', 'status',
        ]));

        return PaystackTransactionResource::make($updatedTransaction);
    }

    /**
     * @return JsonResponse
     */
    public function deleteTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction instanceof PaystackTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $this->transactionService->delete($transaction);

        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
