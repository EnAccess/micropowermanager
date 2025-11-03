<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Modules\Api\Resources;

use Inensus\PaystackPaymentProvider\Models\PaystackCredential;
use Inensus\PaystackPaymentProvider\Models\PaystackTransaction;
use Inensus\PaystackPaymentProvider\Modules\Api\RequestMethod;
use Inensus\PaystackPaymentProvider\Services\PaystackCompanyHashService;

class InitializeTransactionResource extends AbstractApiResource {
    public const RESPONSE_SUCCESS = true;
    public const RESPONSE_KEY_STATUS = 'status';
    public const RESPONSE_KEY_REFERENCE = 'data.reference';
    public const RESPONSE_KEY_AUTHORIZATION_URL = 'data.authorization_url';

    public function __construct(
        private PaystackCredential $paystackCredential,
        private PaystackTransaction $paystackTransaction,
    ) {}

    public function getRequestMethod(): string {
        return RequestMethod::POST->value;
    }

    public function getBodyData(): array {
        // Use static public result URL as callback if available
        $callbackUrl = $this->getCallbackUrl();
        
        $bodyData = [
            'email' => config('paystack-payment-provider.merchant_email'), // MPM merchant email from config
            'amount' => $this->paystackTransaction->getAmount() * 100, // Paystack expects amount in kobo (smallest currency unit) as integer
            'reference' => $this->paystackTransaction->getReferenceId(),
            'callback_url' => $callbackUrl,
            'currency' => $this->paystackTransaction->getCurrency(),
            'metadata' => $this->getMetadata(),
        ];

        // Validate critical fields before sending to Paystack
        if (empty($bodyData['email'])) {
            throw new \InvalidArgumentException('Email is required for Paystack transaction');
        }
        if ($bodyData['amount'] <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        if (empty($bodyData['reference'])) {
            throw new \InvalidArgumentException('Reference is required for Paystack transaction');
        }

        return $bodyData;
    }

    public function getHeaders(): array {
        return [
            'Authorization' => 'Bearer '.$this->paystackCredential->getSecretKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function getPaymentUri(): string {
        $baseUrl = $this->paystackCredential->isLive()
            ? config('paystack-payment-provider.paystack_api_url')
            : config('paystack-payment-provider.paystack_api_url');

        return $baseUrl.'/transaction/initialize';
    }

    public function getAuthorizationUrl(): string {
        $body = $this->getBodyAsArray();

        return $body['data']['authorization_url'] ?? '';
    }

    public function getReference(): string {
        $body = $this->getBodyAsArray();

        return $body['data']['reference'] ?? '';
    }

    private function getCallbackUrl(): string {
        // // Fallback to credential callback URL
        return $this->paystackCredential->getCallbackUrl();
    }

    private function getMetadata(): array {
        $metadata = [
            'order_id' => $this->paystackTransaction->getOrderId(),
            'serial_id' => $this->paystackTransaction->getDeviceSerial(),
            'customer_id' => $this->paystackTransaction->getCustomerId(),
        ];

        // Add agent_id from transaction metadata if available
        $transactionMetadata = $this->paystackTransaction->getMetadata();
        if (isset($transactionMetadata['agent_id']) && $transactionMetadata['agent_id']) {
            $metadata['agent_id'] = $transactionMetadata['agent_id'];
        }

        if(isset($transactionMetadata['public_payment']) && $transactionMetadata['public_payment']) {
            $metadata['public_payment'] = $transactionMetadata['public_payment'];
        }


        return $metadata;
    }
}
