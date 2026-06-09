<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Modules\Api;

use App\Plugins\PesapalPaymentProvider\Modules\Api\Exceptions\PesapalApiException;
use App\Plugins\PesapalPaymentProvider\Modules\Api\Resources\AbstractApiResource;
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
            $body = $response->getBody()->getContents();
            $message = sprintf('PesaPal API returned HTTP %d for %s: %s', $statusCode, $resource->getUri(), substr($body, 0, 500));
            throw new PesapalApiException($statusCode, $resource->getUri(), $body, $message);
        }

        $resource->setBody($response->getBody()->getContents());

        return $resource;
    }
}
