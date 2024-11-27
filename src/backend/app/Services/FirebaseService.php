<?php

namespace App\Services;

use App\Exceptions\NotificationSendFailedException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class FirebaseService {
    public function sendNotify($firebaseToken, $body): string {
        try {
            $httpClient = new Client();
            $request = $httpClient->post(
                config()->get('services.sms.android.url'),
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => config()->get('services.agent.key'),
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'data' => $body,
                        'to' => $firebaseToken,
                    ],
                ]
            );

            return (string) $request->getBody();
        } catch (NotificationSendFailedException $exception) {
            Log::critical('Notification could not send  '.$exception);
            throw $exception;
        }
    }
}
