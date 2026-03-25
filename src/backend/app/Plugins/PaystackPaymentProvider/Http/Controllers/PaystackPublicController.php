<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Http\Controllers;

use App\Plugins\PaystackPaymentProvider\Http\Requests\PublicPaymentRequest;
use App\Plugins\PaystackPaymentProvider\Models\PaystackTransaction;
use App\Plugins\PaystackPaymentProvider\Modules\Api\PaystackApiService;
use App\Plugins\PaystackPaymentProvider\Services\PaystackCompanyHashService;
use App\Plugins\PaystackPaymentProvider\Services\PaystackCredentialService;
use App\Plugins\PaystackPaymentProvider\Services\PaystackTransactionService;
use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class PaystackPublicController extends Controller {
    public function __construct(
        private PaystackCompanyHashService $hashService,
        private PaystackTransactionService $transactionService,
        private PaystackApiService $apiService,
        private PaystackCredentialService $credentialService,
        private CompanyService $companyService,
    ) {}

    public function showPaymentForm(Request $request, string $companyHash, ?int $companyId = null): JsonResponse {
        try {
            // Resolve company id from token if not provided
            $companyId ??= $this->hashService->parseHashFromCompanyId((string) $request->query('ct'));
            if (!$companyId || !$this->hashService->validateHash($companyHash, $companyId)) {
                return response()->json(['error' => 'Invalid company identifier'], 400);
            }

            // Get company information
            $company = $this->companyService->getById($companyId);

            // Check if Paystack is enabled for this company
            $credentials = $this->credentialService->getCredentials();

            return response()->json([
                'company' => [
                    'id' => $company->getId(),
                    'name' => $company->getName(),
                ],
                'supported_currencies' => config('paystack-payment-provider.currency.supported', ['NGN', 'GHS', 'KES', 'ZAR']),
                'default_currency' => config('paystack-payment-provider.currency.default', 'NGN'),
            ]);
        } catch (\Exception $e) {
            Log::error('PaystackPublicController: Failed to show payment form', [
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

            // Get customer ID based on device type
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
                'reference' => $result['provider_data']['reference'],
                'transaction_id' => $result['transaction']->id,
            ]);
        } catch (\Exception $e) {
            Log::error('PaystackPublicController: Failed to initiate payment', [
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

            // Get transaction details
            $transaction = $this->transactionService->getByPaystackReference($reference);
            if (!$transaction instanceof PaystackTransaction) {
                return response()->json(['error' => 'Transaction not found'], 404);
            }

            // Get the main transaction and its token
            $mainTransaction = $transaction->transaction()->first();
            $token = null;
            $tokenStatus = 'pending';

            if ($mainTransaction) {
                $token = $mainTransaction->token()->first();

                // Determine token status based on transaction and token state
                if ($token) {
                    $tokenStatus = 'generated';
                } elseif (in_array($mainTransaction->getAttribute('status'), [0, 1], true)) {
                    // Treat non-generated tokens as processing or pending
                    // requested or success
                    $tokenStatus = 'processing';
                } else {
                    $tokenStatus = 'pending';
                }
            }

            // Verify transaction with Paystack
            $verification = $this->apiService->verifyTransaction($reference);

            $response = [
                'transaction' => [
                    'id' => $transaction->getId(),
                    'amount' => $transaction->getAmount(),
                    'currency' => $transaction->getCurrency(),
                    'serial_id' => $transaction->getDeviceSerial(),
                    'device_type' => $transaction->getDeviceType(),
                    'payment_type' => $mainTransaction?->type,
                    'status' => $transaction->getStatus(),
                    'created_at' => $transaction->getAttribute('created_at'),
                ],
                'verification' => $verification,
                'success' => $verification['status'] === 'success',
                'token_status' => $tokenStatus,
            ];

            // Include token information if available
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
            Log::error('PaystackPublicController: Failed to show result', [
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

            // Verify transaction with Paystack
            $verification = $this->apiService->verifyTransaction($reference);

            return response()->json([
                'success' => $verification['status'] === 'success',
                'verification' => $verification,
            ]);
        } catch (\Exception $e) {
            Log::error('PaystackPublicController: Failed to verify transaction', [
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

            // Validate device exists and is active
            $isValid = $this->transactionService->validateDeviceSerial($deviceSerial, $deviceType);

            return response()->json([
                'valid' => $isValid,
                'device_serial' => $deviceSerial,
                'device_type' => $deviceType,
            ]);
        } catch (\Exception $e) {
            Log::error('PaystackPublicController: Failed to validate device', [
                'error' => $e->getMessage(),
                'company_hash' => $companyHash,
                'company_id' => $companyId,
                'device_serial' => $request->input('device_serial') ?? $request->input('meter_serial'),
                'device_type' => $request->input('device_type', 'meter'),
            ]);

            return response()->json(['error' => 'Failed to validate device'], 500);
        }
    }
}
