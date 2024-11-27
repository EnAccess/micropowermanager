<?php

namespace Inensus\ViberMessaging\Services;

use Illuminate\Support\Facades\Log;
use Inensus\ViberMessaging\Exceptions\WebhookNotCreatedException;
use Viber\Client;

class WebhookService {
    public function createWebHook($credential) {
        $apiKey = $credential->api_token;
        $webhookUrl = $credential->webhook_url;

        try {
            $client = new Client(['token' => $apiKey]);
            $result = $client->setWebhook($webhookUrl);
            $credential->has_webhook_created = true;
            $credential->save();
            Log::info('Webhook created successfully');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            throw new WebhookNotCreatedException($e->getMessage());
        }
    }
}
