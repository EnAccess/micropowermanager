<?php

namespace Inensus\WaveMoneyPaymentProvider\Modules\Api\Resource;

use App\Models\Company;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyCredential;
use Inensus\WaveMoneyPaymentProvider\Models\WaveMoneyTransaction;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\RequestMethod;

class StartTransactionResource extends AbstractApiResource
{

    public const RESPONSE_SUCCESS = 'success';
    public const RESPONSE_KEY_MESSAGE = 'transaction_id';
    public const RESPONSE_KEY_TRANSACTION_ID = 'transaction_id';

    public function __construct(
        private Company              $company,
        private WaveMoneyCredential  $waveMoneyCredential,
        private WaveMoneyTransaction $waveMoneyTransaction)
    {
    }

    public function getRequestMethod(): string
    {
        return RequestMethod::POST;
    }

    public function getBodyData(): array
    {
        return [
            'merchant_id' => $this->waveMoneyCredential->getMerchantId(),
            'order_id' => $this->waveMoneyTransaction->getOrderId(),
            'merchant_reference_id' => $this->waveMoneyTransaction->getReferenceId(),
            'frontend_result_url' => $this->getFrontendCallback(),
            'backend_result_url' => $this->getBackendCallback($this->waveMoneyTransaction->getReferenceId()),
            'amount' => $this->waveMoneyTransaction->getAmount(),
            'time_to_live_in_seconds' => self::REQUEST_TIME_TO_LIVE_IN_SECS,
            'payment_description' => 'MicroPowerManager transaction',
            'currency' => $this->waveMoneyTransaction->getCurrency(),
            'hash' => $this->generatePayloadHash(),
            'merchant_name' => $this->company->getName(),
            'items' => ['payment' => $this->waveMoneyTransaction->getAmount()],
        ];
    }

    public function getQueryParams(): array
    {
        return [];
    }

    public function getUri(): string
    {
        return '/payment';
    }

    private function generatePayloadHash(): string
    {
        return hash_hmac('sha256', implode("", [
            self::REQUEST_TIME_TO_LIVE_IN_SECS,
            $this->waveMoneyCredential->getMerchantId(),
            $this->waveMoneyTransaction->getOrderId(),
            $this->waveMoneyTransaction->getAmount(),
            self::BACKEND_RESULT_WEBHOOK . $this->waveMoneyTransaction->getId() . '/result',
            $this->waveMoneyTransaction->getReferenceId(),]), $this->waveMoneyCredential->getSecretKey());
    }
}

