<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Modules\Api\Resources;

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
}
