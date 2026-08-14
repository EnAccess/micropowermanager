<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Modules\Api\Resources;

use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveCredential;
use App\Plugins\FlutterwavePaymentProvider\Modules\Api\RequestMethod;

class VerifyTransactionResource extends AbstractApiResource {
    public const RESPONSE_SUCCESS = 'success';
    public const RESPONSE_KEY_STATUS = 'status';
    public const RESPONSE_KEY_DATA = 'data';
    public const RESPONSE_KEY_TX_REF = 'data.tx_ref';
    public const RESPONSE_KEY_AMOUNT = 'data.amount';
    public const RESPONSE_KEY_CURRENCY = 'data.currency';
    public const RESPONSE_KEY_TRANSACTION_STATUS = 'data.status';

    // Flutterwave verifies by its own numeric transaction id, not by our tx_ref —
    // that id only becomes available on the redirect callback, unlike Paystack
    // which verifies directly by the reference we generated.
    public function __construct(
        private FlutterwaveCredential $flutterwaveCredential,
        private string $flutterwaveTransactionId,
    ) {}

    public function getRequestMethod(): string {
        return RequestMethod::GET->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBodyData(): array {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array {
        return [
            'Authorization' => 'Bearer '.$this->flutterwaveCredential->getSecretKey(),
            'Accept' => 'application/json',
        ];
    }

    public function getPaymentUri(): string {
        $baseUrl = config('flutterwave-payment-provider.flutterwave_api_url');

        return $baseUrl.'/transactions/'.$this->flutterwaveTransactionId.'/verify';
    }

    public function getTransactionStatus(): string {
        $body = $this->getBodyAsArray();

        return $body['data']['status'] ?? '';
    }

    public function getTransactionAmount(): float {
        $body = $this->getBodyAsArray();

        return (float) ($body['data']['amount'] ?? 0);
    }

    public function getTransactionCurrency(): string {
        $body = $this->getBodyAsArray();

        return $body['data']['currency'] ?? '';
    }

    public function getTxRef(): string {
        $body = $this->getBodyAsArray();

        return $body['data']['tx_ref'] ?? '';
    }
}
