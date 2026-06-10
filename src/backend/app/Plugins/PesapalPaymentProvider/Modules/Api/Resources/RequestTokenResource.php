<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Modules\Api\Resources;

use App\Plugins\PesapalPaymentProvider\Models\PesapalCredential;
use App\Plugins\PesapalPaymentProvider\Modules\Api\RequestMethod;

class RequestTokenResource extends AbstractApiResource {
    public function __construct(
        private PesapalCredential $credential,
        private string $baseUrl,
    ) {}

    public function getRequestMethod(): string {
        return RequestMethod::POST->value;
    }

    /**
     * @return array<string, mixed>
     */
    public function getBodyData(): array {
        return [
            'consumer_key' => $this->credential->getConsumerKey(),
            'consumer_secret' => $this->credential->getConsumerSecret(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getHeaders(): array {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function getPaymentUri(): string {
        return $this->baseUrl.'/api/Auth/RequestToken';
    }

    public function getToken(): ?string {
        $body = $this->getBodyAsArray();

        return $body['token'] ?? null;
    }

    public function getExpiryDate(): ?string {
        $body = $this->getBodyAsArray();

        return $body['expiryDate'] ?? null;
    }

    public function getError(): ?string {
        $parsed = $this->parsePesapalErrorObject();
        if ($parsed !== null) {
            return $parsed;
        }

        $body = $this->getBodyAsArray();
        $error = $body['error'] ?? null;
        if (is_string($error) && $error !== '') {
            return $error;
        }

        $message = $body['message'] ?? null;

        return is_string($message) && $message !== '' ? $message : null;
    }
}
