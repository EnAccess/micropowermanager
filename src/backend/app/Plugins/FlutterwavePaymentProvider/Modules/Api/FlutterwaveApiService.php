<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Modules\Api;

use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use App\Plugins\FlutterwavePaymentProvider\Modules\Api\Exceptions\FlutterwaveApiException;
use App\Plugins\FlutterwavePaymentProvider\Modules\Api\Resources\InitializePaymentResource;
use App\Plugins\FlutterwavePaymentProvider\Modules\Api\Resources\VerifyTransactionResource;
use App\Plugins\FlutterwavePaymentProvider\Services\FlutterwaveCredentialService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class FlutterwaveApiService {
    public function __construct(
        private FlutterwaveApi $api,
        private FlutterwaveCredentialService $credentialService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function initializeTransaction(FlutterwaveTransaction $transaction, ?int $companyId = null): array {
        $credential = $this->credentialService->getCredentials();
        $transactionResource = new InitializePaymentResource($credential, $transaction, $companyId);

        try {
            $response = $this->api->doRequest($transactionResource);
            $body = $response->getBodyAsArray();

            if (($body['status'] ?? null) === InitializePaymentResource::RESPONSE_SUCCESS) {
                $link = $body['data']['link'] ?? '';

                $transaction->payment_url = $link;
                $transaction->save();

                Log::info('Flutterwave Transaction Initialize Success', [
                    'tx_ref' => $transaction->reference_id,
                    'link' => $link,
                ]);

                return [
                    'redirectionUrl' => $link,
                    'reference' => $transaction->reference_id,
                    'error' => null,
                ];
            }

            return [
                'redirectionUrl' => null,
                'reference' => null,
                'error' => 'Failed to initialize transaction: '.($body['message'] ?? 'Unknown error'),
            ];
        } catch (GuzzleException|FlutterwaveApiException $exception) {
            Log::error('Flutterwave Transaction Initialize Exception', [
                'exception_message' => $exception->getMessage(),
                'exception_code' => $exception->getCode(),
                'transaction_reference' => $transaction->reference_id,
                'trace' => $exception->getTraceAsString(),
            ]);

            $transaction->status = FlutterwaveTransaction::STATUS_FAILED;
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
    public function verifyTransaction(string $flutterwaveTransactionId): array {
        $credential = $this->credentialService->getCredentials();
        $transactionResource = new VerifyTransactionResource($credential, $flutterwaveTransactionId);

        try {
            $response = $this->api->doRequest($transactionResource);
            $body = $response->getBodyAsArray();

            if (($body['status'] ?? null) === VerifyTransactionResource::RESPONSE_SUCCESS) {
                $data = $body['data'] ?? [];

                return [
                    'status' => $data['status'] ?? '',
                    'tx_ref' => $data['tx_ref'] ?? '',
                    'amount' => (float) ($data['amount'] ?? 0),
                    'currency' => $data['currency'] ?? '',
                    'error' => null,
                ];
            }

            return [
                'status' => null,
                'tx_ref' => null,
                'amount' => null,
                'currency' => null,
                'error' => 'Failed to verify transaction',
            ];
        } catch (GuzzleException|FlutterwaveApiException $exception) {
            return [
                'status' => null,
                'tx_ref' => null,
                'amount' => null,
                'currency' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }
}
