<?php

namespace App\Plugins\ViberMessaging\Services;

use App\Plugins\ViberMessaging\Models\ViberCredential;
use Illuminate\Support\Facades\Log;
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
