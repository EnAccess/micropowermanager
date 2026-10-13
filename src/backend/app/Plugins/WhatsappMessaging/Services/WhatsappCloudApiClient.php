<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Plugins\WhatsappMessaging\Exceptions\WhatsappMessageNotSentException;
use App\Plugins\WhatsappMessaging\Helpers\WhatsappPhoneNumber;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WhatsappCloudApiClient {
    private const string BASE_URL = 'https://graph.facebook.com/';
    private const int TIMEOUT_SECONDS = 15;

    // The version is the only stored value that becomes part of the base URL, so it is restricted to Meta's "vMAJOR.MINOR" form.
    private const string GRAPH_API_VERSION_PATTERN = '/^v\d+\.\d+$/';

    public function __construct(
        private WhatsappCredentialService $credentialService,
    ) {}

    /**
     * Meta accepting a message does not mean it was delivered; delivery is reported later by webhook.
     *
     * @return string the wamid Meta assigned to the accepted message
     *
     * @throws WhatsappMessageNotSentException
     */
    public function sendMessage(WhatsappOutboundMessage $message): string {
        return $this->postMessage($this->buildMessagePayload($message));
    }

    /**
     * Interactive messages are only accepted inside the customer service window, so this is for replies to an inbound message.
     *
     * @param string                $to      recipient phone number in E.164 format
     * @param array<string, string> $buttons button titles (at most 20 characters) keyed by the id Meta returns when the button is tapped
     *
     * @return string the wamid Meta assigned to the accepted message
     *
     * @throws WhatsappMessageNotSentException
     */
    public function sendInteractiveButtons(string $to, string $body, array $buttons): string {
        return $this->postMessage($this->buildRecipientPayload($to) + [
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $body],
                'action' => [
                    'buttons' => array_map(
                        fn (string $id, string $title): array => ['type' => 'reply', 'reply' => ['id' => $id, 'title' => $title]],
                        array_keys($buttons),
                        $buttons,
                    ),
                ],
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     *
     * @throws WhatsappMessageNotSentException
     */
    public function getPhoneNumber(): array {
        $credential = $this->getCredentials('phone_number_id');

        $response = $this->send($credential, 'GET', $credential->phone_number_id);

        return $response->json();
    }

    /**
     * @return list<array<string, mixed>> every message template of the business account, across all result pages
     *
     * @throws WhatsappMessageNotSentException
     */
    public function getMessageTemplates(): array {
        $credential = $this->getCredentials('business_account_id');
        $query = ['fields' => 'name,status,language,category,components', 'limit' => 100];
        $templates = [];

        do {
            $response = $this->send($credential, 'GET', $credential->business_account_id.'/message_templates', $query);
            array_push($templates, ...$response->json('data', []));
            $query['after'] = $response->json('paging.cursors.after');
        } while ($response->json('paging.next') !== null && $query['after'] !== null);

        return $templates;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @throws WhatsappMessageNotSentException
     */
    private function postMessage(array $payload): string {
        $credential = $this->getCredentials('phone_number_id');

        $response = $this->send($credential, 'POST', $credential->phone_number_id.'/messages', $payload);

        $wamid = $response->json('messages.0.id');

        if (!is_string($wamid) || $wamid === '') {
            throw new WhatsappMessageNotSentException('WhatsApp message not sent: Meta response did not contain a message id');
        }

        return $wamid;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildRecipientPayload(string $to): array {
        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => WhatsappPhoneNumber::toWaId($to),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMessagePayload(WhatsappOutboundMessage $message): array {
        $payload = $this->buildRecipientPayload($message->to);

        if (!$message->isTemplate()) {
            return $payload + [
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $message->body],
            ];
        }

        $template = [
            'name' => $message->templateName,
            'language' => ['code' => $message->templateLanguage],
        ];

        if ($message->templateParameters !== []) {
            $template['components'] = [[
                'type' => 'body',
                'parameters' => array_map(
                    fn (string $parameter): array => ['type' => 'text', 'text' => $parameter],
                    $message->templateParameters,
                ),
            ]];
        }

        return $payload + ['type' => 'template', 'template' => $template];
    }

    /**
     * @param array<string, mixed> $payload sent as the query string for GET and as the JSON body otherwise
     *
     * @throws WhatsappMessageNotSentException
     */
    private function send(WhatsappCredential $credential, string $method, string $path, array $payload = []): Response {
        $options = $payload === [] ? [] : [$method === 'GET' ? 'query' : 'json' => $payload];

        try {
            $response = Http::baseUrl(self::BASE_URL.$credential->graph_api_version.'/')
                ->withToken($credential->access_token)
                ->timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->send($method, $path, $options);
        } catch (ConnectionException $exception) {
            throw WhatsappMessageNotSentException::fromConnectionFailure($exception);
        }

        if ($response->failed()) {
            throw WhatsappMessageNotSentException::fromErrorResponse($response);
        }

        return $response;
    }

    /**
     * @param 'phone_number_id'|'business_account_id' $requiredIdField the Meta id the request is addressed to
     *
     * @throws WhatsappMessageNotSentException
     */
    private function getCredentials(string $requiredIdField): WhatsappCredential {
        $credential = $this->credentialService->getCredentials();

        if (!$credential instanceof WhatsappCredential || empty($credential->access_token) || empty($credential->{$requiredIdField})) {
            throw new WhatsappMessageNotSentException('WhatsApp credentials are not configured. Set the access token and '.str_replace('_', ' ', $requiredIdField).' in the WhatsApp plugin settings.');
        }

        if (preg_match(self::GRAPH_API_VERSION_PATTERN, $credential->graph_api_version) !== 1) {
            throw new WhatsappMessageNotSentException('WhatsApp Graph API version "'.$credential->graph_api_version.'" is invalid. Expected a value like "v26.0".');
        }

        return $credential;
    }
}
