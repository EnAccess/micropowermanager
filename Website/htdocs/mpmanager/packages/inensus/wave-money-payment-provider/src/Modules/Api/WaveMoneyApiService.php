<?php

namespace Inensus\WaveMoneyPaymentProvider\Modules\Api;

use App\Models\Company;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyCredential;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Exceptions\ApiRequestFailedException;
use Inensus\WaveMoneyPaymentProvider\Services\WaveMoneyCredentialService;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Resources\StartTransactionResource;

class WaveMoneyApiService
{
    public function __construct(
        private WaveMoneyApi $api,
        private WaveMoneyCredentialService $credentialService,
    ) {
    }

    public function requestPayment(WaveMoneyTransaction $transaction): array
    {
        $credential = $this->credentialService->getCredentials();
        $transactionResource = new StartTransactionResource($credential, $transaction);
        try {
            $response = $this->api->doRequest($transactionResource);
            $body = $response->getBodyAsArray();

            if (array_key_exists(StartTransactionResource::RESPONSE_KEY_MESSAGE, $body) &&
                $body['message'] === StartTransactionResource::RESPONSE_SUCCESS) {
                $transaction->setExternalTransactionId($body[StartTransactionResource::RESPONSE_KEY_TRANSACTION_ID]);
                $transaction->save();
            }

            return [
                'redirectionUrl' => $transactionResource->getAuthenticateUri() .
                    $body[StartTransactionResource::RESPONSE_KEY_TRANSACTION_ID],
                'error' => null
            ];

        } catch (GuzzleException|ApiRequestFailedException $exception) {
            $transaction->setStatus(WaveMoneyTransaction::STATUS_FAILED);
            $transaction->save();

            return [
                'redirectionUrl' => null,
                'error' => $exception->getMessage()
            ];
        }
    }

}
