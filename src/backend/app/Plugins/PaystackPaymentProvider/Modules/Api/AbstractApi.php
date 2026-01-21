<?php

declare(strict_types=1);

namespace App\Plugins\PaystackPaymentProvider\Modules\Api;

use App\Plugins\PaystackPaymentProvider\Modules\Api\Exceptions\PaystackApiException;
use App\Plugins\PaystackPaymentProvider\Modules\Api\Resources\AbstractApiResource;
use GuzzleHttp\Client;

abstract class AbstractApi {
    public function __construct(private Client $client) {}

    public function doRequest(AbstractApiResource $resource): AbstractApiResource {
        if ($resource->getRequestMethod() === RequestMethod::POST->value) {
            $response = $this->client->post($resource->getPaymentUri(), [
                'json' => $resource->getBodyData(),
                'headers' => $resource->getHeaders(),
            ]);
        } else {
            $response = $this->client->get($resource->getUri(), [
                'headers' => $resource->getHeaders(),
            ]);
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200 && $statusCode !== 201) {
            throw new PaystackApiException($response->getStatusCode(), $resource->getUri(), $response->getBody()->getContents());
        }

        $resource->setBody($response->getBody()->getContents());

        return $resource;
    }
}
