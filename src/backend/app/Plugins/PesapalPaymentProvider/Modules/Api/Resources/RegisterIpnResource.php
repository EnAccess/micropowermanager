<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Modules\Api\Resources;

use App\Plugins\PesapalPaymentProvider\Modules\Api\RequestMethod;

class RegisterIpnResource extends AbstractApiResource {
    public function __construct(
        private string $baseUrl,
        private string $token,
        private string $ipnUrl,
        private string $notificationType = 'GET',
    ) {}

    public function getRequestMethod(): string {
        return RequestMethod::POST->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBodyData(): array {
        return [
            'url' => $this->ipnUrl,
            'ipn_notification_type' => $this->notificationType,
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
        return $this->baseUrl.'/api/URLSetup/RegisterIPN';
    }

    public function getIpnId(): ?string {
        $body = $this->getBodyAsArray();

        return $body['ipn_id'] ?? null;
    }

    public function getError(): ?string {
        $parsed = $this->parsePesapalErrorObject();
        if ($parsed !== null) {
            return $parsed;
        }

        return $this->getBodyAsArray()['message'] ?? null;
    }
}
