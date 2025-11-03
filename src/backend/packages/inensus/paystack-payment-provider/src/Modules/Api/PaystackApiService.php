<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Modules\Api;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\PaystackPaymentProvider\Modules\Api\Exceptions\PaystackApiException;
use Inensus\PaystackPaymentProvider\Modules\Api\Resources\InitializeTransactionResource;
use Inensus\PaystackPaymentProvider\Modules\Api\Resources\VerifyTransactionResource;
use Inensus\PaystackPaymentProvider\Services\PaystackCompanyHashService;
use Inensus\PaystackPaymentProvider\Services\PaystackCredentialService;

class PaystackApiService {
    public function __construct(
        private PaystackApi $api,
        private PaystackCredentialService $credentialService,
        private PaystackCompanyHashService $hashService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function initializeTransaction(PaystackTransaction $transaction, ?int $companyId = null): array {
        $credential = $this->credentialService->getCredentials();
        $transactionResource = new InitializeTransactionResource($credential, $transaction, $this->hashService, $companyId);

        // Log the outgoing request details
        Log::info('Paystack Transaction Initialize Request', [
            'url' => $transactionResource->getPaymentUri(),
            'method' => $transactionResource->getRequestMethod(),
            'headers' => $this->sanitizeHeaders($transactionResource->getHeaders()),
            'body' => $transactionResource->getBodyData(),
            'transaction_reference' => $transaction->getReferenceId(),
            'transaction_amount' => $transaction->getAmount(),
        ]);

        try {
            $response = $this->api->doRequest($transactionResource);
            $body = $response->getBodyAsArray();

            // Log the response details
            Log::info('Paystack Transaction Initialize Response', [
                'body' => $body,
                'transaction_reference' => $transaction->getReferenceId(),
            ]);

            if ($body['status'] === InitializeTransactionResource::RESPONSE_SUCCESS) {
                $reference = $body['data']['reference'] ?? '';
                $authorizationUrl = $body['data']['authorization_url'] ?? '';

                $transaction->setPaystackReference($reference);
                $transaction->setPaymentUrl($authorizationUrl);
                $transaction->save();

                Log::info('Paystack Transaction Initialize Success', [
                    'reference' => $reference,
                    'authorization_url' => $authorizationUrl,
                    'transaction_reference' => $transaction->getReferenceId(),
                ]);

                return [
                    'redirectionUrl' => $authorizationUrl,
                    'reference' => $reference,
                    'error' => null,
                ];
            }

            Log::warning('Paystack Transaction Initialize Failed', [
                'response_body' => $body,
                'transaction_reference' => $transaction->getReferenceId(),
            ]);

            return [
                'redirectionUrl' => null,
                'reference' => null,
                'error' => 'Failed to initialize transaction: '.($body['message'] ?? 'Unknown error'),
            ];
        } catch (GuzzleException|PaystackApiException $exception) {
            Log::error('Paystack Transaction Initialize Exception', [
                'exception_message' => $exception->getMessage(),
                'exception_code' => $exception->getCode(),
                'transaction_reference' => $transaction->getReferenceId(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $transaction->setStatus(PaystackTransaction::STATUS_FAILED);
            $transaction->save();

            return [
                'redirectionUrl' => null,
                'reference' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array {
        $credential = $this->credentialService->getCredentials();
        $transactionResource = new VerifyTransactionResource($credential, $reference);

        try {
            $response = $this->api->doRequest($transactionResource);
            $body = $response->getBodyAsArray();

            if ($body['status'] === VerifyTransactionResource::RESPONSE_SUCCESS) {
                $data = $body['data'] ?? [];

                return [
                    'status' => $data['status'] ?? '',
                    'amount' => (int) ($data['amount'] ?? 0),
                    'currency' => $data['currency'] ?? '',
                    'gateway_response' => $data['gateway_response'] ?? '',
                    'error' => null,
                ];
            }

            return [
                'status' => null,
                'amount' => null,
                'currency' => null,
                'gateway_response' => null,
                'error' => 'Failed to verify transaction',
            ];
        } catch (GuzzleException|PaystackApiException $exception) {
            return [
                'status' => null,
                'amount' => null,
                'currency' => null,
                'gateway_response' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * Sanitize headers to remove sensitive information from logs.
     *
     * @param array<string, mixed> $headers
     *
     * @return array<string, mixed>
     */
    private function sanitizeHeaders(array $headers): array {
        $sanitized = $headers;
        if (isset($sanitized['Authorization'])) {
            $sanitized['Authorization'] = 'Bearer ****';
        }

        return $sanitized;
    }
}
