<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Modules\Api;

use App\Plugins\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Api\Exceptions\ApiRequestFailedException;
use App\Plugins\WaveMoneyPaymentProvider\Modules\Api\Resources\StartTransactionResource;
use App\Plugins\WaveMoneyPaymentProvider\Services\WaveMoneyCredentialService;
use GuzzleHttp\Exception\GuzzleException;

class WaveMoneyApiService {
    public function __construct(
        private WaveMoneyApi $api,
        private WaveMoneyCredentialService $credentialService,
    ) {}

    /**
     * @return array{
     *     redirectionUrl: string|null,
     *     error: string|null
     * }
     */
    public function requestPayment(WaveMoneyTransaction $transaction): array {
        $credential = $this->credentialService->getCredentials();
        $transactionResource = new StartTransactionResource($credential, $transaction);
        try {
            $response = $this->api->doRequest($transactionResource);
            $body = $response->getBodyAsArray();

            if (array_key_exists(StartTransactionResource::RESPONSE_KEY_MESSAGE, $body)
                && $body['message'] === StartTransactionResource::RESPONSE_SUCCESS) {
                $transaction->setExternalTransactionId($body[StartTransactionResource::RESPONSE_KEY_TRANSACTION_ID]);
                $transaction->save();
            }

            return [
                'redirectionUrl' => $transactionResource->getAuthenticateUri().
                    $body[StartTransactionResource::RESPONSE_KEY_TRANSACTION_ID],
                'error' => null,
            ];
        } catch (GuzzleException|ApiRequestFailedException $exception) {
            $transaction->setStatus(WaveMoneyTransaction::STATUS_FAILED);
            $transaction->save();

            return [
                'redirectionUrl' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }
}
