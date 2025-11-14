<?php

declare(strict_types=1);

namespace Inensus\PaystackPaymentProvider\Modules\Api;

use GuzzleHttp\Client;

class PaystackApi extends AbstractApi {
    public function __construct(Client $client) {
        parent::__construct($client);
    }
}
