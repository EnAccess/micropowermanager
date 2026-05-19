<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Modules\Api\Resources;

use App\Plugins\PesapalPaymentProvider\Models\PesapalCredential;
use App\Plugins\PesapalPaymentProvider\Models\PesapalTransaction;
use App\Plugins\PesapalPaymentProvider\Modules\Api\RequestMethod;

class SubmitOrderRequestResource extends AbstractApiResource {
    public function __construct(
        private PesapalCredential $credential,
        private PesapalTransaction $transaction,
        private string $baseUrl,
        private string $token,
        private string $callbackUrl,
        private ?string $payerPhoneNumber = null,
    ) {}

    public function getRequestMethod(): string {
        return RequestMethod::POST->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBodyData(): array {
        $ipnId = $this->credential->getIpnId();
        if (empty($ipnId)) {
            throw new \InvalidArgumentException('PesaPal IPN is not registered for this merchant; save credentials again to register one.');
        }

        $merchantReference = $this->buildMerchantReference();
        $description = $this->buildDescription();

        // PesaPal requires at least an email_address or phone_number on billing_address.
        $billingAddress = $this->buildBillingAddress();

        return [
            'id' => $merchantReference,
            'currency' => $this->transaction->getCurrency() ?: $this->credential->getCurrency(),
            'amount' => round($this->transaction->getAmount(), 2),
            'description' => $description,
            'callback_url' => $this->callbackUrl,
            'notification_id' => $ipnId,
            'billing_address' => $billingAddress,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array {
        return [
            'Authorization' => 'Bearer '.$this->token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function getPaymentUri(): string {
        return $this->baseUrl.'/api/Transactions/SubmitOrderRequest';
    }

    public function getOrderTrackingId(): ?string {
        $body = $this->getBodyAsArray();

        return $body['order_tracking_id'] ?? null;
    }

    public function getMerchantReference(): ?string {
        $body = $this->getBodyAsArray();

        return $body['merchant_reference'] ?? null;
    }

    public function getRedirectUrl(): ?string {
        $body = $this->getBodyAsArray();

        return $body['redirect_url'] ?? null;
    }

    public function getError(): ?string {
        $parsed = $this->parsePesapalErrorObject();
        if ($parsed !== null) {
            return $parsed;
        }

        $body = $this->getBodyAsArray();
        $status = $body['status'] ?? null;
        if ($status !== null && (string) $status !== '200') {
            return $body['message'] ?? "Pesapal SubmitOrderRequest returned status {$status}";
        }

        return null;
    }

    private function buildMerchantReference(): string {
        // PesaPal restricts `id` to <= 50 chars, alphanumeric + dashes.
        $candidate = $this->transaction->getReferenceId();
        $candidate = preg_replace('/[^A-Za-z0-9-]/', '-', $candidate) ?? $candidate;

        return substr($candidate, 0, 50);
    }

    private function buildDescription(): string {
        $serial = $this->transaction->getDeviceSerial() ?: 'payment';
        $type = $this->transaction->getDeviceType() ?: 'energy';
        $description = "MPM {$type} payment for {$serial}";

        return substr($description, 0, 100);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildBillingAddress(): array {
        $billingAddress = [];
        $email = $this->credential->getMerchantEmail();
        if (!empty($email)) {
            $billingAddress['email_address'] = $email;
        }
        if (!empty($this->payerPhoneNumber)) {
            $billingAddress['phone_number'] = $this->payerPhoneNumber;
        }

        if ($billingAddress === []) {
            throw new \InvalidArgumentException('PesaPal SubmitOrderRequest requires either a customer phone number or a merchant email.');
        }

        return $billingAddress;
    }
}
