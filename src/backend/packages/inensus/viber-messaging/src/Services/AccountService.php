<?php

namespace Inensus\ViberMessaging\Services;

use Illuminate\Support\Facades\Log;
use Viber\Client;

class AccountService {
    public function getAccountInfo($credential) {
        $apiKey = $credential->api_token;

        try {
            $client = new Client(['token' => $apiKey]);
            $result = $client->getAccountInfo();

            return $result->getData()['uri'];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \Exception($e->getMessage());
        }
    }
}
