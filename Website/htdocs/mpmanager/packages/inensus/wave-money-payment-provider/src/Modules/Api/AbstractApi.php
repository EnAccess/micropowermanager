<?php

declare(strict_types=1);

namespace Inensus\WaveMoneyPaymentProvider\Modules\Api;


use GuzzleHttp\Client;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Exceptions\ApiRequestFailedException;
use Inensus\WaveMoneyPaymentProvider\Modules\Api\Resource\AbstractApiResource;

abstract class AbstractApi
{
    public function __construct(private Client $client)
    {
    }


    public function doRequest(AbstractApiResource $resource): AbstractApiResource
    {
        if ($resource->getRequestMethod() === RequestMethod::POST) {
            $response = $this->client->post($resource->getUri() . 'payment', [
                'body' => $resource->getBodyData(),
                'headers' => $resource->getHeaders()
            ]);
        } else {
            $response = $this->client->get($resource->getUri(), ['headers' => $resource->getHeaders()]);
        }

        if ($response->getStatusCode() !== 200 || $response->getStatusCode() !== 201) {
            throw new ApiRequestFailedException($response->getStatusCode(), $resource->getUri(),
                $response->getBody()->getContents());
        }

        $resource->setBody($response->getBody()->getContents());

        return $resource;

    }
}
