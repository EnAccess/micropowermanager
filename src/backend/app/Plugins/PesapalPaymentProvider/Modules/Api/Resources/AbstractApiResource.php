<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Modules\Api\Resources;

abstract class AbstractApiResource {
    public string $body = '';

    abstract public function getRequestMethod(): string;

    /**
     * @return array<string, mixed>
     */
    abstract public function getBodyData(): array;

    /**
     * @return array<string, mixed>
     */
    abstract public function getHeaders(): array;

    abstract public function getPaymentUri(): string;

    public function getUri(): string {
        return $this->getPaymentUri();
    }

    /**
     * @return array<string, mixed>
     */
    public function getBodyAsArray(): array {
        return json_decode($this->body, true) ?? [];
    }

    protected function parsePesapalErrorObject(): ?string {
        $error = $this->getBodyAsArray()['error'] ?? null;
        if (!is_array($error)) {
            return null;
        }
        $hasContent = array_any($error, fn ($value) => $value !== null && $value !== '');
        if (!$hasContent) {
            return null;
        }

        $message = $error['message'] ?? null;
        if (is_string($message) && $message !== '') {
            return $message;
        }

        return json_encode($error) ?: null;
    }
}
