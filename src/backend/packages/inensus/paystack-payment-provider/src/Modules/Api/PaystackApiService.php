<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Modules\Api;

use GuzzleHttp\Exception\GuzzleException;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\PaystackPaymentProvider\Modules\Api\Exceptions\PaystackApiException;
use Inensus\PaystackPaymentProvider\Modules\Api\Resources\InitializeTransactionResource;
use Inensus\PaystackPaymentProvider\Modules\Api\Resources\VerifyTransactionResource;
use Inensus\PaystackPaymentProvider\Services\PaystackCredentialService;

class PaystackApiService {
    public function __construct(
        private PaystackApi $api,
        private PaystackCredentialService $credentialService,
    ) {}

    public function initializeTransaction(PaystackTransaction $transaction): array {
        $credential = $this->credentialService->getCredentials();
        $transactionResource = new InitializeTransactionResource($credential, $transaction);

        try {
            $response = $this->api->doRequest($transactionResource);
            $body = $response->getBodyAsArray();

            if ($body['status'] === InitializeTransactionResource::RESPONSE_SUCCESS) {
                $reference = $body['data']['reference'] ?? '';
                $authorizationUrl = $body['data']['authorization_url'] ?? '';

                $transaction->setPaystackReference($reference);
                $transaction->setPaymentUrl($authorizationUrl);
                $transaction->save();

                return [
                    'redirectionUrl' => $authorizationUrl,
                    'reference' => $reference,
                    'error' => null,
                ];
            }

            return [
                'redirectionUrl' => null,
                'reference' => null,
                'error' => 'Failed to initialize transaction',
            ];
        } catch (GuzzleException|PaystackApiException $exception) {
            $transaction->setStatus(PaystackTransaction::STATUS_FAILED);
            $transaction->save();

            return [
                'redirectionUrl' => null,
                'reference' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }

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
}
