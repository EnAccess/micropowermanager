<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Api;

use GuzzleHttp\Client;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Exceptions\ApiRequestFailedException;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Resources\AbstractApiResource;

abstract class AbstractApi {
    public function __construct(private Client $client) {}

    public function doRequest(AbstractApiResource $resource): AbstractApiResource {
        if ($resource->getRequestMethod() === RequestMethod::POST) {
            $response = $this->client->post($resource->getPaymentUri(), [
                'form_params' => $resource->getBodyData(),
                'headers' => $resource->getHeaders(),
            ]);
        } else {
            $response = $this->client->get($resource->getUri(), ['headers' => $resource->getHeaders()]);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200 && $statusCode !== 201) {
            throw new ApiRequestFailedException($response->getStatusCode(), $resource->getUri(), $response->getBody()->getContents());
        }

        $resource->setBody($response->getBody()->getContents());

        return $resource;
    }
}
