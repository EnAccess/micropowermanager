<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Api\Resource;

abstract class AbstractApiResource
{
    protected string $body;
    protected const BACKEND_RESULT_WEBHOOK = '/api/wave/transaction/';
    protected const FRONTEND_END_RESULT_WEBHOOK = '/#/wave/transaction-result';
    protected const REQUEST_TIME_TO_LIVE_IN_SECS = 120;

    abstract public function getRequestMethod(): string;

    abstract public function getBodyData(): array;

    abstract public function getQueryParams(): array;

    abstract public function getUri(): string;

    public function getHeaders(): array
    {
        return ['Accept' => 'application/json'];
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function getBody(): string
    {
        return $this->body;
    }
    public function getBodyAsArray(): array
    {
        return json_decode($this->body , true);
    }

    public function getBackendCallback(string $referenceId):string
    {
        return config('app.url'). self::BACKEND_RESULT_WEBHOOK.$referenceId;
    }
    public function getFrontendCallback():string
    {
        return config('app.url'). self::FRONTEND_END_RESULT_WEBHOOK;
    }
}
