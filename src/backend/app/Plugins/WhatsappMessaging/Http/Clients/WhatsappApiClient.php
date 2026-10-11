<?php

namespace App\Plugins\WhatsappMessaging\Http\Clients;

use App\Plugins\WhatsappMessaging\Exceptions\WhatsappConnectionFailedException;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class WhatsappApiClient {
    private const GRAPH_API_URL = 'https://graph.facebook.com';
    private const TIMEOUT_SECONDS = 15;

    /**
     * @return array{display_phone_number: string, verified_name: string|null, quality_rating: string|null}
     */
    public function getPhoneNumberInfo(WhatsappCredential $credential): array {
        try {
            $response = $this->request($credential)->get($credential->phone_number_id, [
                'fields' => 'display_phone_number,verified_name,quality_rating',
            ]);
        } catch (ConnectionException) {
            throw new WhatsappConnectionFailedException('Could not reach the WhatsApp Cloud API. Please try again later.');
        }

        if ($response->failed()) {
            $metaMessage = $response->json('error.message');

            throw new WhatsappConnectionFailedException(
                'WhatsApp rejected the request: '.(is_string($metaMessage) ? $metaMessage : 'HTTP '.$response->status())
            );
        }

        $displayPhoneNumber = $response->json('display_phone_number');
        if (!is_string($displayPhoneNumber)) {
            throw new WhatsappConnectionFailedException('WhatsApp returned an unexpected response for the phone number.');
        }

        return [
            'display_phone_number' => $displayPhoneNumber,
            'verified_name' => $response->json('verified_name'),
            'quality_rating' => $response->json('quality_rating'),
        ];
    }

    private function request(WhatsappCredential $credential): PendingRequest {
        return Http::baseUrl(self::GRAPH_API_URL.'/'.$credential->graph_api_version)
            ->withToken($credential->access_token)
            ->acceptJson()
            ->timeout(self::TIMEOUT_SECONDS);
    }
}
