<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Modules\Api\Resources;

use Inensus\PaystackPaymentProvider\Models\PaystackCredential;
use Inensus\PaystackPaymentProvider\Modules\Api\RequestMethod;

class VerifyTransactionResource extends AbstractApiResource {
    public const RESPONSE_SUCCESS = true;
    public const RESPONSE_KEY_STATUS = 'status';
    public const RESPONSE_KEY_DATA = 'data';
    public const RESPONSE_KEY_REFERENCE = 'data.reference';
    public const RESPONSE_KEY_AMOUNT = 'data.amount';
    public const RESPONSE_KEY_CURRENCY = 'data.currency';
    public const RESPONSE_KEY_GATEWAY_RESPONSE = 'data.gateway_response';

    public function __construct(
        private PaystackCredential $paystackCredential,
        private string $reference,
    ) {}

    public function getRequestMethod(): string {
        return RequestMethod::GET->value;
    }

    public function getBodyData(): array {
        return [];
    }

    public function getHeaders(): array {
        return [
            'Authorization' => 'Bearer '.$this->paystackCredential->getSecretKey(),
            'Accept' => 'application/json',
        ];
    }

    public function getPaymentUri(): string {
        $baseUrl = $this->paystackCredential->isLive()
            ? 'https://api.paystack.co'
            : 'https://api.paystack.co';

        return $baseUrl.'/transaction/verify/'.$this->reference;
    }

    public function getTransactionStatus(): string {
        $body = $this->getBodyAsArray();

        return $body['data']['status'] ?? '';
    }

    public function getTransactionAmount(): int {
        $body = $this->getBodyAsArray();

        return (int) ($body['data']['amount'] ?? 0);
    }

    public function getTransactionCurrency(): string {
        $body = $this->getBodyAsArray();

        return $body['data']['currency'] ?? '';
    }

    public function getGatewayResponse(): string {
        $body = $this->getBodyAsArray();

        return $body['data']['gateway_response'] ?? '';
    }
}
