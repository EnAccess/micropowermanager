<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Http\Controllers;

use App\Enums\DeviceType;
use App\Plugins\FlutterwavePaymentProvider\Http\Requests\PublicPaymentRequest;
use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use App\Plugins\FlutterwavePaymentProvider\Modules\Api\FlutterwaveApiService;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveCompanyHashService;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveCredentialService;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveTransactionService;
use App\Services\CompanyService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

#[Group('Plugins / Flutterwave')]
class FlutterwavePublicController extends Controller {
    public function __construct(
        private FlutterwaveCompanyHashService $hashService,
        private FlutterwaveTransactionService $transactionService,
        private FlutterwaveApiService $apiService,
        private FlutterwaveCredentialService $credentialService,
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

            // Check if Flutterwave is enabled for this company
            $credentials = $this->credentialService->getCredentials();

            return response()->json([
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                ],
                'supported_currencies' => config('flutterwave-payment-provider.currency.supported', ['NGN', 'GHS', 'KES', 'ZAR', 'USD']),
                'default_currency' => config('flutterwave-payment-provider.currency.default', 'NGN'),
            ]);
        } catch (\Exception $e) {
            Log::error('FlutterwavePublicController: Failed to show payment form', [
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
            $deviceType = $validatedData['device_type'] ?? DeviceType::Meter->value;
            $deviceSerial = $validatedData['device_serial'];

            // Get customer ID based on device type
            if ($deviceType === DeviceType::SolarHomeSystem->value) {
                $customerId = $this->transactionService->getCustomerIdBySHSSerial($deviceSerial);
            } else {
                $customerId = $this->transactionService->getCustomerIdByMeterSerial($deviceSerial);
            }

            if (!$customerId) {
                return response()->json(['error' => 'Customer not found for device'], 400);
            }

            $sender = $this->transactionService->getCustomerPhoneByCustomerId($customerId) ?? '';

            $result = $this->transactionService->initiatePayment(
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
            Log::error('FlutterwavePublicController: Failed to initiate payment', [
                'error' => $e->getMessage(),
                'company_hash' => $companyHash,
                'company_id' => $companyId,
                'request_data' => $request->all(),
            ]);

            return response()->json(['error' => 'Failed to initiate payment'], 500);
        }
    }

    /**
     * Flutterwave's redirect callback returns both `tx_ref` (our reference) and its
     * own numeric `transaction_id` as query params — we need the former to find our
     * record and the latter to verify with Flutterwave's API.
     */
    public function showResult(Request $request, string $companyHash, ?int $companyId = null): JsonResponse {
        try {
            $companyId ??= $this->hashService->parseHashFromCompanyId((string) $request->query('ct'));
            if (!$companyId || !$this->hashService->validateHash($companyHash, $companyId)) {
                return response()->json(['error' => 'Invalid company identifier'], 400);
            }

            $reference = $request->query('reference') ?? $request->query('tx_ref');
            if (!$reference) {
                return response()->json(['error' => 'Transaction reference required'], 400);
            }

            // Get transaction details
            $transaction = $this->transactionService->getByFlutterwaveReference($reference);
            if (!$transaction instanceof FlutterwaveTransaction) {
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

            // Verify transaction with Flutterwave using its own transaction id
            $transactionId = $request->query('transaction_id') ?? $transaction->external_transaction_id;
            $verification = $transactionId
                ? $this->apiService->verifyTransaction((string) $transactionId)
                : ['status' => null, 'error' => 'No Flutterwave transaction id available yet'];

            $response = [
                'transaction' => [
                    'id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'serial_id' => $transaction->serial_id,
                    'device_type' => $transaction->getDeviceType(),
                    'payment_type' => $mainTransaction?->type,
                    'status' => $transaction->status,
                    'created_at' => $transaction->getAttribute('created_at'),
                ],
                'verification' => $verification,
                'success' => $verification['status'] === 'successful',
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
            Log::error('FlutterwavePublicController: Failed to show result', [
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

            $transactionId = $request->query('transaction_id');
            if (!$transactionId) {
                return response()->json(['error' => 'Flutterwave transaction id required'], 400);
            }

            // Verify transaction with Flutterwave
            $verification = $this->apiService->verifyTransaction((string) $transactionId);

            return response()->json([
                'success' => $verification['status'] === 'successful',
                'verification' => $verification,
            ]);
        } catch (\Exception $e) {
            Log::error('FlutterwavePublicController: Failed to verify transaction', [
                'error' => $e->getMessage(),
                'company_hash' => $companyHash,
                'company_id' => $companyId,
                'transaction_id' => $request->query('transaction_id'),
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
            $deviceType = $request->string('device_type', DeviceType::Meter->value)->toString();

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
            Log::error('FlutterwavePublicController: Failed to validate device', [
                'error' => $e->getMessage(),
                'company_hash' => $companyHash,
                'company_id' => $companyId,
                'device_serial' => $request->input('device_serial') ?? $request->input('meter_serial'),
                'device_type' => $request->string('device_type', DeviceType::Meter->value)->toString(),
            ]);

            return response()->json(['error' => 'Failed to validate device'], 500);
        }
    }
}
