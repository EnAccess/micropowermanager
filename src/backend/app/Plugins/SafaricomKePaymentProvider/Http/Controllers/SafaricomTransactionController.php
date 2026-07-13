<?php

declare(strict_types=1);

namespace App\Plugins\SafaricomKePaymentProvider\Http\Controllers;

use App\Enums\DeviceType;
use App\Plugins\SafaricomKePaymentProvider\Http\Requests\SafaricomSTKPushRequest;
use App\Plugins\SafaricomKePaymentProvider\Http\Resources\SafaricomTransactionResource;
use App\Plugins\SafaricomKePaymentProvider\Models\SafaricomTransaction;
use App\Plugins\SafaricomKePaymentProvider\Services\SafaricomTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

class SafaricomTransactionController extends Controller {
    public function __construct(
        private SafaricomTransactionService $transactionService,
    ) {}

    public function getTransactions(Request $request): JsonResponse {
        $perPage = $request->integer('per_page', 15);

        return response()->json($this->transactionService->getAll($perPage));
    }

    /**
     * @return SafaricomTransactionResource|JsonResponse
     */
    public function getTransaction(int $id) {
        $transaction = $this->transactionService->getById($id);
        if (!$transaction instanceof SafaricomTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        return SafaricomTransactionResource::make($transaction);
    }

    public function validateDevice(Request $request): JsonResponse {
        $request->validate([
            'device_serial' => ['required', 'string', 'min:3', 'max:100'],
            'device_type' => ['nullable', 'string', Rule::in([DeviceType::Meter->value, DeviceType::SolarHomeSystem->value])],
        ]);
        $deviceSerial = (string) $request->input('device_serial');
        $deviceType = (string) ($request->input('device_type') ?? DeviceType::Meter->value);

        $isValid = $this->transactionService->validateDeviceSerial($deviceSerial, $deviceType);
        $customerId = $isValid
            ? $this->transactionService->getCustomerIdByDeviceSerial($deviceSerial, $deviceType)
            : null;

        return response()->json([
            'valid' => $isValid,
            'device_serial' => $deviceSerial,
            'device_type' => $deviceType,
            'customer_id' => $customerId,
        ]);
    }

    public function initiateStkPush(SafaricomSTKPushRequest $request): JsonResponse {
        $data = $request->validated();
        $serialId = $data['device_serial'] ?? ($data['serial_id'] ?? null);
        $deviceType = (string) ($data['device_type'] ?? DeviceType::Meter->value);

        if (!$serialId || !$this->transactionService->validateDeviceSerial((string) $serialId, $deviceType)) {
            return response()->json([
                'error' => 'Unknown device serial — pick a registered meter or SHS.',
            ], 422);
        }

        $customerId = $this->transactionService->getCustomerIdByDeviceSerial((string) $serialId, $deviceType);
        if ($customerId === null) {
            return response()->json([
                'error' => 'No customer is linked to that device — assign one before charging.',
            ], 422);
        }

        try {
            $result = $this->transactionService->initiatePayment(
                amount: (float) $data['amount'],
                sender: (string) $data['phone_number'],
                message: (string) ($data['transaction_desc'] ?? $data['account_reference'] ?? $serialId),
                type: (string) ($data['type'] ?? 'energy'),
                customerId: $customerId,
                serialId: $serialId,
            );
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'reference_id' => $result['provider_data']['reference_id'],
                'checkout_request_id' => $result['provider_data']['checkout_request_id'],
                'merchant_request_id' => $result['provider_data']['merchant_request_id'],
                'customer_message' => $result['provider_data']['customer_message'],
            ],
        ]);
    }

    /**
     * Get the status of a transaction.
     *
     * Polled by the STK Push page while the customer is entering their PIN.
     * For pending transactions this hits Daraja's STK Push Query so we don't
     * have to wait for the (frequently delayed) async callback. Once the
     * transaction is resolved (success/failure/abandoned) further calls are
     * cheap — they short-circuit on the cached terminal status.
     */
    public function getStatus(Request $request, string $referenceId): JsonResponse {
        $transaction = $this->transactionService->getByReferenceId($referenceId);
        if (!$transaction instanceof SafaricomTransaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $companyId = (int) $request->attributes->get('companyId');
        $snapshot = $this->transactionService->queryStatus($transaction, $companyId);
        $transaction->refresh();

        return response()->json([
            'data' => array_merge($snapshot, [
                'reference_id' => $transaction->reference_id,
                'phone_number' => $transaction->phone_number,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'mpesa_receipt_number' => $transaction->mpesa_receipt_number,
                'transaction_date' => $transaction->transaction_date,
            ]),
        ]);
    }
}
