<?php

namespace App\Plugins\WhatsappMessaging\Http\Clients;

use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Helpers\WhatsappPhoneNumber;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WhatsappApiClient {
    private const string BASE_URL = 'https://graph.facebook.com/';

    // The version becomes part of the base URL, so it is restricted to Meta's "vMAJOR.MINOR" form.
    private const string GRAPH_API_VERSION_PATTERN = '/^v\d+\.\d+$/';

    /**
     * @param array<string, mixed> $content the type-specific part of the message, e.g. ['type' => 'text', 'text' => [...]]
     *
     * @return string the wamid; acceptance is not delivery, which Meta reports later by webhook
     */
    public function sendMessage(WhatsappCredential $credential, string $to, array $content): string {
        $wamid = $this->post($credential, $credential->phone_number_id.'/messages', [
            'messaging_product' => 'whatsapp',
            'to' => WhatsappPhoneNumber::toWaId($to),
            ...$content,
        ])->json('messages.0.id');

        return is_string($wamid) ? $wamid : throw new WhatsappApiException('WhatsApp message not sent: Meta returned no message id.');
    }

    /** @param array<string, mixed> $query */
    public function get(WhatsappCredential $credential, string $path, array $query = []): Response {
        return $this->send($credential, 'GET', $path, ['query' => $query]);
    }

    /** @param array<string, mixed> $body */
    public function post(WhatsappCredential $credential, string $path, array $body): Response {
        return $this->send($credential, 'POST', $path, ['json' => $body]);
    }

    /** @param array<string, mixed> $options */
    private function send(WhatsappCredential $credential, string $method, string $path, array $options): Response {
        if (empty($credential->access_token) || preg_match(self::GRAPH_API_VERSION_PATTERN, $credential->graph_api_version) !== 1) {
            throw new WhatsappApiException('WhatsApp credentials are incomplete. Check the access token and Graph API version (e.g. "v26.0") in the plugin settings.');
        }

        try {
            $response = Http::baseUrl(self::BASE_URL.$credential->graph_api_version.'/')
                ->withToken($credential->access_token)->timeout(15)->acceptJson()->send($method, $path, $options);
        } catch (ConnectionException $exception) {
            throw new WhatsappApiException('Could not reach the WhatsApp Cloud API ('.$exception->getMessage().')', 0, $exception);
        }

        return $response->failed() ? throw WhatsappApiException::fromErrorResponse($response) : $response;
    }
}
