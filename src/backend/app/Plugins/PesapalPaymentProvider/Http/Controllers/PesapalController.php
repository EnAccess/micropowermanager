<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Http\Controllers;

use App\Plugins\PesapalPaymentProvider\Http\Requests\TransactionInitializeRequest;
use App\Plugins\PesapalPaymentProvider\Http\Resources\PesapalTransactionResource;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PesapalController extends Controller {
    public function __construct(
        private PesapalTransactionService $transactionService,
    ) {}

    public function initializeTransaction(TransactionInitializeRequest $request): JsonResponse {
        $customerId = (int) $request->input('customer_id');
        $serialId = $request->input('device_serial');
        $amount = (float) $request->input('amount');
        $sender = $this->transactionService->getCustomerPhoneByCustomerId($customerId) ?? '';

        $result = $this->transactionService->initializePayment(
            amount: $amount,
            sender: $sender,
            message: $serialId,
            type: 'energy',
            customerId: $customerId,
            serialId: $serialId,
        );

        return response()->json([
            'data' => [
                'redirect_url' => $result['provider_data']['redirect_url'],
                'order_tracking_id' => $result['provider_data']['order_tracking_id'],
                'merchant_reference' => $result['provider_data']['merchant_reference'],
                'error' => null,
            ],
        ]);
    }

    public function verifyTransaction(Request $request, string $orderTrackingId): JsonResponse {
        $transaction = $this->transactionService->getByOrderTrackingId($orderTrackingId);
        if (!$transaction instanceof PesapalTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $companyId = (int) $request->attributes->get('companyId');
        $result = $this->transactionService->syncStatusFromApi($transaction, $companyId);

        if ($result['error']) {
            return response()->json(['error' => $result['error']], 400);
        }

        return response()->json($result);
    }

    public function getTransactions(Request $request): JsonResponse {
        $perPage = $request->integer('per_page', 15);
        $transactions = $this->transactionService->getAll($perPage);

        return response()->json($transactions);
    }

    /**
     * @return PesapalTransactionResource|JsonResponse
     */
    public function getTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction instanceof PesapalTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        return PesapalTransactionResource::make($transaction);
    }

    /**
     * @return PesapalTransactionResource|JsonResponse
     */
    public function updateTransaction(Request $request, int $id) {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction instanceof PesapalTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $supportedCurrencies = config('pesapal-payment-provider.currency.supported', ['KES', 'UGX', 'TZS', 'USD']);
        $request->validate([
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'in:'.implode(',', $supportedCurrencies)],
            'status' => ['sometimes', 'integer', 'in:-1,0,1,2,3'],
        ]);

        $updatedTransaction = $this->transactionService->update($transaction, $request->only([
            'amount', 'currency', 'status',
        ]));

        return PesapalTransactionResource::make($updatedTransaction);
    }

    public function deleteTransaction(Request $request, int $id): JsonResponse {
        $transaction = $this->transactionService->getById($id);

        if (!$transaction instanceof PesapalTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $this->transactionService->delete($transaction);

        return response()->json(['message' => 'Transaction deleted successfully']);
    }
}
