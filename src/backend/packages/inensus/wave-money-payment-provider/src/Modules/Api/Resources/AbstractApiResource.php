<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Api\Resources;

abstract class AbstractApiResource {
    protected string $body;
    protected const REQUEST_TIME_TO_LIVE_IN_SECS = 120;

    abstract public function getRequestMethod(): string;

    abstract public function getBodyData(): array;

    abstract public function getQueryParams(): array;

    abstract public function getBackendCallback(): string;

    abstract public function getFrontendCallback(): string;

    public function getUri(): string {
        return config('wave-money-payment-provider.api_uri');
    }

    public function getPaymentUri(): string {
        return config('wave-money-payment-provider.api_uri').'/payment';
    }

    public function getHeaders(): array {
        return ['Accept' => 'application/json'];
    }

    public function setBody(string $body): void {
        $this->body = $body;
    }

    public function getBody(): string {
        return $this->body;
    }

    public function getBodyAsArray(): array {
        return json_decode($this->body, true);
    }

    public function getAuthenticateUri(): string {
        return config('wave-money-payment-provider.api_uri').'/authenticate?transaction_id=';
    }
}
