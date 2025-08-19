<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Modules\Api\Resources;

use Inensus\PaystackPaymentProvider\Modules\Api\RequestMethod;

abstract class AbstractApiResource {
    protected string $body = '';

    abstract public function getRequestMethod(): string;

    abstract public function getBodyData(): array;

    abstract public function getHeaders(): array;

    abstract public function getPaymentUri(): string;

    public function getUri(): string {
        return $this->getPaymentUri();
    }

    public function setBody(string $body): void {
        $this->body = $body;
    }

    public function getBody(): string {
        return $this->body;
    }

    public function getBodyAsArray(): array {
        return json_decode($this->body, true) ?? [];
    }
}
