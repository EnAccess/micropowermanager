<?php

declare(strict_types=1);

namespace App\Plugins\FlutterwavePaymentProvider\Modules\Api\Resources;

use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveCredential;
use App\Plugins\FlutterwavePaymentProvider\Models\FlutterwaveTransaction;
use App\Plugins\FlutterwavePaymentProvider\Modules\Api\RequestMethod;

class InitializePaymentResource extends AbstractApiResource {
    public const RESPONSE_SUCCESS = 'success';
    public const RESPONSE_KEY_STATUS = 'status';
    public const RESPONSE_KEY_LINK = 'data.link';

    public function __construct(
        private FlutterwaveCredential $flutterwaveCredential,
        private FlutterwaveTransaction $flutterwaveTransaction,
        private ?int $companyId = null,
    ) {}

    public function getRequestMethod(): string {
        return RequestMethod::POST->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBodyData(): array {
        $redirectUrl = $this->getRedirectUrl();

        // Flutterwave requires a customer email. Same limitation as the Paystack
        // integration: MPM does not store a per-transaction customer email, so
        // the tenant's merchant email is used as a fallback.
        $merchantEmail = $this->flutterwaveCredential->merchant_email
            ?? config('flutterwave-payment-provider.merchant_email');

        $bodyData = [
            'tx_ref' => $this->flutterwaveTransaction->reference_id,
            'amount' => $this->flutterwaveTransaction->amount,
            'currency' => $this->flutterwaveTransaction->currency,
            'redirect_url' => $redirectUrl,
            'customer' => [
                'email' => $merchantEmail,
            ],
            'customizations' => [
                'title' => $this->flutterwaveCredential->merchant_name,
            ],
            'meta' => $this->getMetadata(),
        ];

        if (empty($bodyData['customer']['email'])) {
            throw new \InvalidArgumentException('Email is required for Flutterwave transaction');
        }
        if ($bodyData['amount'] <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }
        if (empty($bodyData['tx_ref'])) {
            throw new \InvalidArgumentException('tx_ref is required for Flutterwave transaction');
        }

        return $bodyData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array {
        return [
            'Authorization' => 'Bearer '.$this->flutterwaveCredential->getSecretKey(),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function getPaymentUri(): string {
        return config('flutterwave-payment-provider.flutterwave_api_url').'/payments';
    }

    public function getLink(): string {
        $body = $this->getBodyAsArray();

        return $body['data']['link'] ?? '';
    }

    private function getRedirectUrl(): string {
        return $this->flutterwaveCredential->callback_url;
    }

    /**
     * @return array<string, mixed>
     */
    private function getMetadata(): array {
        $metadata = [
            'order_id' => $this->flutterwaveTransaction->order_id,
            'serial_id' => $this->flutterwaveTransaction->serial_id,
            'customer_id' => $this->flutterwaveTransaction->customer_id,
            'company_id' => $this->companyId,
        ];

        $transactionMetadata = $this->flutterwaveTransaction->getMetadata();
        if (isset($transactionMetadata['agent_id']) && $transactionMetadata['agent_id']) {
            $metadata['agent_id'] = $transactionMetadata['agent_id'];
        }

        if (isset($transactionMetadata['public_payment']) && $transactionMetadata['public_payment']) {
            $metadata['public_payment'] = $transactionMetadata['public_payment'];
        }

        return $metadata;
    }
}
