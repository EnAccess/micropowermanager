<?php

namespace Inensus\WaveMoneyPaymentProvider\Modules\Api;

use App\Models\Company;
use GuzzleHttp\Exception\GuzzleException;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyCredential;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Data\TransactionCallbackData;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Exceptions\ApiRequestFailedException;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Resource\StartTransactionResource;
use Inensus\WaveMoneyPaymentProvider\Services\WaveMoneyCredentialService;
use Ramsey\Uuid\Uuid;

class WaveMoneyApiService
{

    public function __construct(
        private WaveMoneyApi $api,
        private WaveMoneyCredentialService $credentialService,
    )
    {
    }

    public function requestPayment(WaveMoneyTransaction $transaction): void
    {
        $credential = $this->credentialService->getCredentials();
        $transactionResource = new StartTransactionResource($credential, $transaction);
        try {
            $response = $this->api->doRequest($transactionResource);
            $body = $response->getBodyAsArray();
            if(array_key_exists(StartTransactionResource::RESPONSE_KEY_MESSAGE, $body) && $body['message'] === StartTransactionResource::RESPONSE_SUCCESS) {
                $transaction->setExternalTransactionId($body[StartTransactionResource::RESPONSE_KEY_TRANSACTION_ID]);
                $transaction->save();
            }

        } catch (GuzzleException|ApiRequestFailedException $exception) {
            $transaction->setStatus(WaveMoneyTransaction::STATUS_FAILED);
            $transaction->save();
        }
    }

    public function transactionCallback(TransactionCallbackData $callbackData): void
    {
        $transaction = $this->waveMoneyTransaction->findByOrderId($callbackData->getOrderId());
        if($transaction === null) {
            // TODO: log it
            return;
        }
        if($callbackData->mapTransactionStatus($callbackData->getStatus()) === TransactionCallbackData::STATUS_FAILURE) {
            $transaction->setStatus(WaveMoneyTransaction::STATUS_SUCCESS);
            $transaction->setExternalTransactionId($callbackData->getTransactionId());
            $transaction->save();
        }
    }

}
