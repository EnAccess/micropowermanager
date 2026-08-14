<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Http\Controllers;

use App\Plugins\FlutterwavePaymentProvider\Http\Requests\TransactionInitializeRequest;
use App\Plugins\FlutterwavePaymentProvider\Http\Resources\FlutterwaveTransactionResource;
use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use App\Plugins\FlutterwavePaymentProvider\Modules\Api\FlutterwaveApiService;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveTransactionService;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveWebhookService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

#[Group('Plugins / Flutterwave', 'API endpoints for integrating with Flutterwave payment services')]
class FlutterwaveController extends Controller {
    public function __construct(
        private FlutterwaveTransactionService $transactionService,
        private FlutterwaveApiService $apiService,
        private FlutterwaveWebhookService $webhookService,
    ) {}

    public function initializeTransaction(TransactionInitializeRequest $request): JsonResponse {
        $customerId = (int) $request->input('customer_id');
        $serialId = $request->input('device_serial');
        $amount = (float) $request->input('amount');
        $sender = $this->transactionService->getCustomerPhoneByCustomerId($customerId) ?? '';

        $result = $this->transactionService->initiatePayment(
            amount: $amount,
            sender: $sender,
            message: $serialId,
            type: 'energy',
            customerId: $customerId,
            serialId: $serialId,
        );

        return response()->json([
            'data' => [
                'redirectionUrl' => $result['provider_data']['redirect_url'],
                'reference' => $result['provider_data']['reference'],
                'error' => null,
            ],
        ]);
    }

    /**
     * @return JsonResponse
     */
    public function webhookCallback(Request $request, int $companyId) {
        if (!$this->webhookService->verifyWebhook($request)) {
            return response()->json(['error' => 'Invalid webhook signature'], 401);
        }

        try {
            $processed = $this->webhookService->processWebhook($request, $companyId);
        } catch (\Exception $e) {
            Log::error('FlutterwaveController: Failed to handle webhook', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'company_id' => $companyId,
            ]);

            // Flutterwave retries on a non-2xx, which is what we want for a transient failure.
            return response()->json(['error' => 'Failed to process webhook'], 500);
        }

        return response()->json(['status' => $processed ? 'success' : 'ignored']);
    }

    /**
     * @return JsonResponse
     */
    public function verifyTransaction(Request $request, string $transactionId) {
        $result = $this->apiService->verifyTransaction($transactionId);

        if ($result['error']) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json($result);
    }

    /**
     * @return JsonResponse
     */
    public function getTransactions(Request $request) {
        $perPage = $request->integer('per_page', 15);
        $transactions = $this->transactionService->getAll($perPage);

        return response()->json($transactions);
    }

    /**
     * @return FlutterwaveTransactionResource|JsonResponse
     */
    public function getTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction instanceof FlutterwaveTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        return FlutterwaveTransactionResource::make($transaction);
    }

    /**
     * @return FlutterwaveTransactionResource|JsonResponse
     */
    public function updateTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction instanceof FlutterwaveTransaction) {
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

        return FlutterwaveTransactionResource::make($updatedTransaction);
    }

    /**
     * @return JsonResponse
     */
    public function deleteTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction instanceof FlutterwaveTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $this->transactionService->delete($transaction);

        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
