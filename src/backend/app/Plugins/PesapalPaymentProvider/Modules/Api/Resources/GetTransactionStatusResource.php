<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Modules\Api\Resources;

use App\Plugins\PesapalPaymentProvider\Modules\Api\RequestMethod;

class GetTransactionStatusResource extends AbstractApiResource {
    // PesaPal v3 status_code values (see https://developer.pesapal.com).
    public const STATUS_INVALID = 0;
    public const STATUS_COMPLETED = 1;
    public const STATUS_FAILED = 2;
    public const STATUS_REVERSED = 3;

    public function __construct(
        private string $baseUrl,
        private string $token,
        private string $orderTrackingId,
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
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/json',
        ];
    }

    public function getPaymentUri(): string {
        return $this->baseUrl.'/api/Transactions/GetTransactionStatus?orderTrackingId='.urlencode($this->orderTrackingId);
    }

    public function getStatusCode(): ?int {
        $body = $this->getBodyAsArray();
        if (!array_key_exists('status_code', $body)) {
            return null;
        }

        return (int) $body['status_code'];
    }

    public function getStatusDescription(): string {
        $body = $this->getBodyAsArray();

        return (string) ($body['payment_status_description'] ?? '');
    }

    public function getAmount(): float {
        $body = $this->getBodyAsArray();

        return (float) ($body['amount'] ?? 0);
    }

    public function getCurrency(): string {
        $body = $this->getBodyAsArray();

        return (string) ($body['currency'] ?? '');
    }

    public function getPaymentMethod(): string {
        $body = $this->getBodyAsArray();

        return (string) ($body['payment_method'] ?? '');
    }

    public function getConfirmationCode(): string {
        $body = $this->getBodyAsArray();

        return (string) ($body['confirmation_code'] ?? '');
    }

    public function getMerchantReference(): string {
        $body = $this->getBodyAsArray();

        return (string) ($body['merchant_reference'] ?? '');
    }

    public function getError(): ?string {
        return $this->parsePesapalErrorObject();
    }
}
