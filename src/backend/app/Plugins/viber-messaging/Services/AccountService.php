<?php

namespace Inensus\ViberMessaging\Services;

use Illuminate\Support\Facades\Log;
use Inensus\ViberMessaging\Models\ViberCredential;
use Viber\Client;

class AccountService {
    public function getAccountInfo(ViberCredential $credential): string {
        $apiKey = $credential->api_token;

        try {
            $client = new Client(['token' => $apiKey]);
            $result = $client->getAccountInfo();

            return $result->getData()['uri'];
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }
}
