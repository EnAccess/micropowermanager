<?php

declare(strict_types=1);

namespace App\Plugins\PesapalPaymentProvider\Modules\Api;

use GuzzleHttp\Client;

class PesapalApi extends AbstractApi {
    public function __construct(Client $client) {
        parent::__construct($client);
    }
}
