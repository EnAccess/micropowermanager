<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Http\Controllers;

use App\Plugins\PesapalPaymentProvider\Http\Requests\PublicPaymentRequest;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCompanyHashService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalCredentialService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalIpnService;
use App\Plugins\PesapalPaymentProvider\Services\PesapalTransactionService;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class PesapalPublicController extends Controller {
    public function __construct(
        private PesapalCompanyHashService $hashService,
        private PesapalTransactionService $transactionService,
        private PesapalCredentialService $credentialService,
        private PesapalIpnService $ipnService,
        private CompanyService $companyService,
    ) {}

    public function showPaymentForm(Request $request, string $companyHash, ?int $companyId = null): JsonResponse {
        try {
            $companyId ??= $this->hashService->parseHashFromCompanyId((string) $request->query('ct'));
            if (!$companyId || !$this->hashService->validateHash($companyHash, $companyId)) {
                return response()->json(['error' => 'Invalid company identifier'], 400);
            }

            $company = $this->companyService->getById($companyId);
            $credentials = $this->credentialService->getCredentials();

            return response()->json([
                'company' => [
                    'id' => $company->getId(),
                    'name' => $company->getName(),
                ],
                'currency' => $credentials->getCurrency(),
                'supported_currencies' => config('pesapal-payment-provider.currency.supported', ['KES', 'UGX', 'TZS', 'USD']),
            ]);
        } catch (\Exception $e) {
            Log::error('PesapalPublicController: Failed to show payment form', [
                'error' => $e->getMessage(),
                'company_hash' => $companyHash,
                'company_id' => $companyId,
            ]);

            return response()->json(['error' => 'Service temporarily unavailable'], 500);
        }
    }

    public function initiatePayment(PublicPaymentRequest $request, string $companyHash, ?int $companyId = null): JsonResponse {
        try {
            $companyId ??= $this->hashService->parseHashFromCompanyId((string) $request->query('ct'));
            if (!$companyId || !$this->hashService->validateHash($companyHash, $companyId)) {
                return response()->json(['error' => 'Invalid company identifier'], 400);
            }

            $validatedData = $request->validated();
            $deviceType = $validatedData['device_type'] ?? 'meter';
            $deviceSerial = $validatedData['device_serial'];

            if ($deviceType === 'shs') {
                $customerId = $this->transactionService->getCustomerIdBySHSSerial($deviceSerial);
            } else {
                $customerId = $this->transactionService->getCustomerIdByMeterSerial($deviceSerial);
            }

            if (!$customerId) {
                return response()->json(['error' => 'Customer not found for device'], 400);
            }

            $sender = $this->transactionService->getCustomerPhoneByCustomerId($customerId) ?? '';

            $result = $this->transactionService->initializePayment(
                amount: (float) $validatedData['amount'],
                sender: $sender,
                message: $deviceSerial,
                type: 'energy',
                customerId: $customerId,
                serialId: $deviceSerial,
            );

            return response()->json([
                'success' => true,
                'redirect_url' => $result['provider_data']['redirect_url'],
                'order_tracking_id' => $result['provider_data']['order_tracking_id'],
                'merchant_reference' => $result['provider_data']['merchant_reference'],
                'transaction_id' => $result['transaction']->id,
            ]);
        } catch (\Exception $e) {
            Log::error('PesapalPublicController: Failed to initiate payment', [
                'error' => $e->getMessage(),
                'company_hash' => $companyHash,
                'company_id' => $companyId,
                'request_data' => $request->all(),
            ]);

            return response()->json(['error' => 'Failed to initiate payment'], 500);
        }
    }

    public function showResult(Request $request, string $companyHash, ?int $companyId = null): JsonResponse {
        try {
            $companyId ??= $this->hashService->parseHashFromCompanyId((string) $request->query('ct'));
            if (!$companyId || !$this->hashService->validateHash($companyHash, $companyId)) {
                return response()->json(['error' => 'Invalid company identifier'], 400);
            }

            $reference = $request->query('reference');
            if (!$reference) {
                return response()->json(['error' => 'Transaction reference required'], 400);
            }

            $transaction = $this->transactionService->getByReferenceId($reference);
            if (!$transaction instanceof PesapalTransaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            $mainTransaction = $transaction->transaction()->first();
            $token = null;
            $tokenStatus = 'pending';

            if ($mainTransaction) {
                $token = $mainTransaction->token()->first();

                if ($token) {
                    $tokenStatus = 'generated';
                } elseif (in_array($mainTransaction->getAttribute('status'), [0, 1], true)) {
                    $tokenStatus = 'processing';
                } else {
                    $tokenStatus = 'pending';
                }
            }

            $verification = $transaction->getOrderTrackingId()
                ? $this->transactionService->syncStatusFromApi($transaction, $companyId)
                : ['status_code' => null, 'error' => 'No order tracking id yet'];

            $response = [
                'transaction' => [
                    'id' => $transaction->getId(),
                    'amount' => $transaction->getAmount(),
                    'currency' => $transaction->getCurrency(),
                    'serial_id' => $transaction->getDeviceSerial(),
                    'device_type' => $transaction->getDeviceType(),
                    'payment_type' => $mainTransaction?->type,
                    'status' => $transaction->getStatus(),
                    'order_tracking_id' => $transaction->getOrderTrackingId(),
                    'merchant_reference' => $transaction->getMerchantReference(),
                    'created_at' => $transaction->getAttribute('created_at'),
                ],
                'verification' => $verification,
                'success' => isset($verification['status_code']) && $verification['status_code'] === 1,
                'token_status' => $tokenStatus,
            ];

            if ($token) {
                $response['token'] = [
                    'token' => $token->token,
                    'token_type' => $token->token_type,
                    'token_unit' => $token->token_unit,
                    'token_amount' => $token->token_amount,
                ];
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('PesapalPublicController: Failed to show result', [
                'error' => $e->getMessage(),
                'company_hash' => $companyHash,
                'company_id' => $companyId,
                'reference' => $request->query('reference'),
            ]);

            return response()->json(['error' => 'Failed to retrieve transaction details'], 500);
        }
    }

    public function verifyTransaction(Request $request, string $companyHash, ?int $companyId = null): JsonResponse {
        try {
            $companyId ??= $this->hashService->parseHashFromCompanyId((string) $request->query('ct'));
            if (!$companyId || !$this->hashService->validateHash($companyHash, $companyId)) {
                return response()->json(['error' => 'Invalid company identifier'], 400);
            }

            $reference = $request->query('reference');
            if (!$reference) {
                return response()->json(['error' => 'Transaction reference required'], 400);
            }

            $transaction = $this->transactionService->getByReferenceId($reference);
            if (!$transaction instanceof PesapalTransaction || in_array($transaction->getOrderTrackingId(), [null, '', '0'], true)) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            $verification = $this->transactionService->syncStatusFromApi($transaction, $companyId);

            return response()->json([
                'success' => isset($verification['status_code']) && $verification['status_code'] === 1,
                'verification' => $verification,
            ]);
        } catch (\Exception $e) {
            Log::error('PesapalPublicController: Failed to verify transaction', [
                'error' => $e->getMessage(),
                'company_hash' => $companyHash,
                'company_id' => $companyId,
                'reference' => $request->query('reference'),
            ]);

            return response()->json(['error' => 'Failed to verify transaction'], 500);
        }
    }

    public function validateMeter(Request $request, string $companyHash, ?int $companyId = null): JsonResponse {
        try {
            $companyId ??= $this->hashService->parseHashFromCompanyId((string) $request->query('ct'));
            if (!$companyId || !$this->hashService->validateHash($companyHash, $companyId)) {
                return response()->json(['error' => 'Invalid company identifier'], 400);
            }

            $deviceSerial = $request->input('device_serial') ?? $request->input('meter_serial');
            $deviceType = $request->input('device_type', 'meter');

            if (!$deviceSerial) {
                return response()->json(['error' => 'Device serial required'], 400);
            }

            $isValid = $this->transactionService->validateDeviceSerial($deviceSerial, $deviceType);

            return response()->json([
                'valid' => $isValid,
                'device_serial' => $deviceSerial,
                'device_type' => $deviceType,
            ]);
        } catch (\Exception $e) {
            Log::error('PesapalPublicController: Failed to validate device', [
                'error' => $e->getMessage(),
                'company_hash' => $companyHash,
                'company_id' => $companyId,
                'device_serial' => $request->input('device_serial') ?? $request->input('meter_serial'),
                'device_type' => $request->input('device_type', 'meter'),
            ]);

            return response()->json(['error' => 'Failed to validate device'], 500);
        }
    }

    public function handleIpn(Request $request, int $companyId): JsonResponse {
        try {
            $this->ipnService->processIpn($request, $companyId);
        } catch (\Exception $e) {
            Log::error('PesapalPublicController: Failed to handle IPN', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
                'payload' => $request->all(),
            ]);
        }

        // PesaPal expects a 200 response so it stops retrying the IPN.
        return response()->json(['status' => 'received']);
    }
}
