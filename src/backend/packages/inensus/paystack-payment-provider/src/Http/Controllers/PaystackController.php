<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\PaystackPaymentProvider\Http\Requests\TransactionInitializeRequest;
use Inensus\PaystackPaymentProvider\Http\Resources\PaystackResource;
use Inensus\PaystackPaymentProvider\Http\Resources\PaystackTransactionResource;
use Inensus\PaystackPaymentProvider\Modules\Api\PaystackApiService;
use Inensus\PaystackPaymentProvider\Modules\Transaction\PaystackTransactionService;
use Inensus\PaystackPaymentProvider\Services\PaystackWebhookService;

class PaystackController extends Controller {
    public function __construct(
        private PaystackTransactionService $transactionService,
        private PaystackApiService $apiService,
        private PaystackWebhookService $webhookService,
    ) {}

    public function startTransaction(TransactionInitializeRequest $request): PaystackResource {
        $transaction = $request->get('paystackTransaction');

        return PaystackResource::make($this->apiService->initializeTransaction($transaction));
    }

    public function webhookCallback(Request $request) {
        // Verify webhook signature
        if (!$this->webhookService->verifyWebhook($request)) {
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        // Process the webhook
        $this->webhookService->processWebhook($request);

        return response()->json(['status' => 'success']);
    }

    public function verifyTransaction(Request $request, string $reference) {
        $result = $this->apiService->verifyTransaction($reference);

        if ($result['error']) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json($result);
    }

    public function getTransactions(Request $request) {
        $transactions = $this->transactionService->getAll();

        return PaystackTransactionResource::collection($transactions);
    }

    public function getTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        return PaystackTransactionResource::make($transaction);
    }

    public function updateTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $request->validate([
            'amount' => 'sometimes|numeric|min:0',
            'currency' => 'sometimes|string|in:NGN,USD,GHS,KES,ZAR',
            'status' => 'sometimes|integer|in:0,1,2,3',
        ]);

        $updatedTransaction = $this->transactionService->update($transaction, $request->only([
            'amount', 'currency', 'status',
        ]));

        return PaystackTransactionResource::make($updatedTransaction);
    }

    public function deleteTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $this->transactionService->delete($transaction);

        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
