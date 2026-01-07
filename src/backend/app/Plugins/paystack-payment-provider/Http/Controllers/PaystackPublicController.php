<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Http\Controllers;

use App\Services\CompanyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Inensus\PaystackPaymentProvider\Http\Requests\PublicPaymentRequest;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\PaystackPaymentProvider\Modules\Api\PaystackApiService;
use Inensus\PaystackPaymentProvider\Services\PaystackCompanyHashService;
use Inensus\PaystackPaymentProvider\Services\PaystackCredentialService;
use Inensus\PaystackPaymentProvider\Services\PaystackTransactionService;
use Ramsey\Uuid\Uuid;

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

            // Validate device serial and amount
            $validatedData = $request->validated();

            // Get agent_id from query parameters if present
            $agentId = $request->query('agent');

            $deviceType = $validatedData['device_type'] ?? 'meter';
            $deviceSerial = $validatedData['device_serial'];

            // Get customer ID based on device type
            if ($deviceType === 'shs') {
                $customerId = $this->transactionService->getCustomerIdBySHSSerial($deviceSerial);
            } else {
                $customerId = $this->transactionService->getCustomerIdByMeterSerial($deviceSerial);
            }

            // Create Paystack transaction
            $transaction = $this->transactionService->createPublicTransaction([
                'amount' => $validatedData['amount'],
                'currency' => $validatedData['currency'],
                'serial_id' => $deviceSerial,
                'device_type' => $deviceType,
                'customer_id' => $customerId,
                'order_id' => Uuid::uuid4()->toString(),
                'reference_id' => Uuid::uuid4()->toString(),
                'agent_id' => $agentId ? (int) $agentId : null,
            ]);

            // Initialize Paystack transaction with company ID for callback URL
            $result = $this->apiService->initializeTransaction($transaction, $companyId);

            if ($result['error']) {
                return response()->json(['error' => $result['error']], 400);
            }

            return response()->json([
                'success' => true,
                'redirection_url' => $result['redirectionUrl'],
                'reference' => $result['reference'],
                'transaction_id' => $transaction->getId(),
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
