<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi;

use App\Models\Address\Address;
use App\Models\ApplianceRate;
use App\Models\Sms;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsappBusinessApi\Exceptions\MessageNotSentException;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappCredential;
use App\Plugins\WhatsappBusinessApi\Services\WhatsappCredentialService;
use App\Services\MainSettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adapts WhatsApp Business API notifications to MPM's SMS gateway interface.
 * Dispatches transactional notifications using Meta Graph API Cloud templates.
 */
class WhatsappGateway {
    private const string BASE_URL = 'https://graph.facebook.com/v18.0';

    public function __construct(
        private WhatsappCredentialService $credentialService,
        private MainSettingsService $mainSettingsService,
    ) {}

    public function sendSms(
        string $body,
        string $receiver,
        ?Sms $registeredSms = null,
    ): void {
        try {
            if (!$registeredSms instanceof Sms) {
                Log::warning('WhatsApp Business API requires an Sms record to resolve templates.');
                throw new MessageNotSentException('No SMS record provided');
            }

            $credentials = $this->credentialService->getCredentials();

            if (!$credentials || empty($credentials->access_token) || empty($credentials->phone_number_id)) {
                throw new MessageNotSentException('WhatsApp Business API credentials not configured');
            }

            // Verify opt-in
            $personId = $this->resolvePersonId($registeredSms);
            if ($personId) {
                $optIn = WhatsappBusinessOptIn::query()->where('person_id', $personId)->first();
                if (!$optIn || !$optIn->opted_in) {
                    Log::info('Customer has not opted in for WhatsApp messages', ['person_id' => $personId, 'receiver' => $receiver]);
                    // Abort sending silently since it's an expected business rule, or throw exception?
                    // We throw an exception to mark SMS as failed.
                    throw new MessageNotSentException('Customer has not opted in for WhatsApp messages');
                }
            } else {
                Log::warning('Could not resolve Person ID for opt-in check. Assuming not opted in.', ['receiver' => $receiver]);
                throw new MessageNotSentException('Could not resolve Person ID for opt-in check');
            }

            $trigger = $registeredSms->trigger;
            $payload = $this->buildPayload($trigger, $receiver, $credentials, $body);

            $url = self::BASE_URL.'/'.$credentials->phone_number_id.'/messages';

            $response = Http::withToken($credentials->access_token)
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error('WhatsApp API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw new MessageNotSentException('WhatsApp message sending failed: '.$response->body());
            }

            $data = $response->json();
            $messageId = $data['messages'][0]['id'] ?? null;

            if ($messageId) {
                $registeredSms->update(['uuid' => substr((string) $messageId, 0, 191)]);
            }
        } catch (MessageNotSentException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            Log::error('WhatsApp message sending failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw new MessageNotSentException('WhatsApp message sending failed: '.$exception->getMessage(), (int) $exception->getCode(), $exception);
        }
    }

    /**
     * Sends an interactive list menu message via Meta Graph API.
     * Tapping the menu button opens a page containing selectable options/buttons.
     *
     * @param array<int, array{id: string, title: string, description?: string}> $options
     */
    public function sendInteractiveMenu(
        string $receiver,
        string $bodyText,
        array $options,
        string $buttonText = 'Select Option',
        ?string $headerText = null,
        ?string $footerText = null,
        ?string $phoneNumberId = null,
    ): ?string {
        try {
            $credentials = $this->credentialService->getCredentials();

            if (!$credentials || empty($credentials->access_token) || empty($credentials->phone_number_id)) {
                Log::warning('WhatsApp credentials not configured for sending interactive menu.');

                return null;
            }

            $cleanReceiver = str_replace(['+', ' '], '', $receiver);

            $rows = array_map(function (array $option): array {
                $row = [
                    'id' => (string) $option['id'],
                    'title' => mb_substr((string) $option['title'], 0, 24),
                ];
                if (!empty($option['description'])) {
                    $row['description'] = mb_substr((string) $option['description'], 0, 72);
                }

                return $row;
            }, $options);

            $action = [
                'button' => mb_substr($buttonText, 0, 20),
                'sections' => [
                    [
                        'title' => 'Self-Service Options',
                        'rows' => $rows,
                    ],
                ],
            ];

            $interactive = [
                'type' => 'list',
                'body' => [
                    'text' => $bodyText,
                ],
                'action' => $action,
            ];

            if ($headerText) {
                $interactive['header'] = [
                    'type' => 'text',
                    'text' => mb_substr($headerText, 0, 60),
                ];
            }

            if ($footerText) {
                $interactive['footer'] = [
                    'text' => mb_substr($footerText, 0, 60),
                ];
            }

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $cleanReceiver,
                'type' => 'interactive',
                'interactive' => $interactive,
            ];

            $targetPhoneId = $phoneNumberId ?: $credentials->phone_number_id;
            $url = self::BASE_URL.'/'.$targetPhoneId.'/messages';

            $response = Http::withToken($credentials->access_token)
                ->post($url, $payload);

            if (!$response->successful() && $targetPhoneId !== $credentials->phone_number_id && !empty($credentials->phone_number_id)) {
                Log::warning("WhatsApp send interactive menu failed on phone $targetPhoneId ({$response->status()}). Retrying with default phone {$credentials->phone_number_id}.");
                $fallbackUrl = self::BASE_URL.'/'.$credentials->phone_number_id.'/messages';
                $response = Http::withToken($credentials->access_token)->post($fallbackUrl, $payload);
            }

            if (!$response->successful()) {
                Log::error('WhatsApp interactive menu API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            return $data['messages'][0]['id'] ?? null;
        } catch (\Throwable $exception) {
            Log::error('Failed to dispatch WhatsApp interactive menu: '.$exception->getMessage());

            return null;
        }
    }

    /**
     * Sends a direct text message via Meta Graph API within the active 24h customer care window.
     */
    public function sendTextMessage(string $receiver, string $bodyText, ?string $phoneNumberId = null): ?string {
        try {
            $credentials = $this->credentialService->getCredentials();

            if (!$credentials || empty($credentials->access_token) || empty($credentials->phone_number_id)) {
                Log::warning('WhatsApp credentials not configured for sending text message.');

                return null;
            }

            $cleanReceiver = str_replace(['+', ' '], '', $receiver);

            $payload = [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $cleanReceiver,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $bodyText,
                ],
            ];

            $targetPhoneId = $phoneNumberId ?: $credentials->phone_number_id;
            $url = self::BASE_URL.'/'.$targetPhoneId.'/messages';

            $response = Http::withToken($credentials->access_token)
                ->post($url, $payload);

            if (!$response->successful() && $targetPhoneId !== $credentials->phone_number_id && !empty($credentials->phone_number_id)) {
                Log::warning("WhatsApp send text failed on phone $targetPhoneId ({$response->status()}). Retrying with default phone {$credentials->phone_number_id}.");
                $fallbackUrl = self::BASE_URL.'/'.$credentials->phone_number_id.'/messages';
                $response = Http::withToken($credentials->access_token)->post($fallbackUrl, $payload);
            }

            if (!$response->successful()) {
                Log::error('WhatsApp text message API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $data = $response->json();

            return $data['messages'][0]['id'] ?? null;
        } catch (\Throwable $exception) {
            Log::error('Failed to dispatch WhatsApp text message: '.$exception->getMessage());

            return null;
        }
    }

    private function resolvePersonId(Sms $sms): ?int {
        $trigger = $sms->trigger;

        if ($trigger instanceof Transaction) {
            $device = $trigger->device;
            if ($device !== null && !empty($device->person_id)) {
                return (int) $device->person_id;
            }

            $appliance = $trigger->appliance;
            if ($appliance !== null && !empty($appliance->person_id)) {
                return (int) $appliance->person_id;
            }

            $paygo = $trigger->paygoAppliance;
            if ($paygo !== null && !empty($paygo->person_id)) {
                return (int) $paygo->person_id;
            }

            $nonPaygo = $trigger->nonPaygoAppliance;
            if ($nonPaygo !== null && !empty($nonPaygo->person_id)) {
                return (int) $nonPaygo->person_id;
            }
        }

        if ($trigger instanceof ApplianceRate) {
            if ($trigger->appliancePerson && !empty($trigger->appliancePerson->person_id)) {
                return (int) $trigger->appliancePerson->person_id;
            }
        }

        $receiverStr = (string) $sms->receiver;
        $clean = preg_replace('/[^0-9]/', '', $receiverStr) ?? '';
        $candidates = array_unique(array_filter([
            $receiverStr,
            '+'.$clean,
            $clean,
            ltrim($clean, '0'),
            '0'.ltrim($clean, '0'),
        ]));

        $address = Address::query()->whereIn('owner_type', ['person', Person::class])
            ->where(function ($query) use ($candidates, $clean): void {
                $query->whereIn('phone', $candidates);
                if ($clean !== '') {
                    $query->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$clean]);
                }
            })
            ->first();

        return $address ? (int) $address->owner_id : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(mixed $trigger, string $receiver, WhatsappCredential $credentials, string $body = ''): array {
        // Strip leading '+' and any spaces for WhatsApp API
        $receiver = str_replace(['+', ' '], '', $receiver);

        if ($trigger instanceof Transaction) {
            return $this->buildTransactionTemplatePayload($trigger, $receiver, $credentials);
        }

        if ($trigger instanceof ApplianceRate) {
            return $this->buildReminderTemplatePayload($trigger, $receiver, $credentials);
        }

        if ($body !== '') {
            return [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $receiver,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $body,
                ],
            ];
        }

        throw new MessageNotSentException('Unsupported trigger type for WhatsApp templates: '.($trigger ? $trigger::class : 'null'));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTransactionTemplatePayload(
        Transaction $transaction,
        string $receiver,
        WhatsappCredential $credentials,
    ): array {
        $templateName = $credentials->transaction_confirmation_template;
        if (empty($templateName)) {
            throw new MessageNotSentException('Transaction confirmation template not configured');
        }

        $currency = $this->mainSettingsService->getAll()->first()->currency ?? 'USD';

        $token = $transaction->token;
        $tokenValue = $token ? $token->token : 'N/A';
        $amount = $transaction->amount.' '.$currency;

        return [
            'messaging_product' => 'whatsapp',
            'to' => $receiver,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'en'], // In MVP we assume English, or we could fetch from settings
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $amount],
                            ['type' => 'text', 'text' => $tokenValue],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildReminderTemplatePayload(
        ApplianceRate $rate,
        string $receiver,
        WhatsappCredential $credentials,
    ): array {
        $templateName = $credentials->reminder_template;
        if (empty($templateName)) {
            throw new MessageNotSentException('Reminder template not configured');
        }

        $currency = $this->mainSettingsService->getAll()->first()->currency ?? 'USD';
        $amount = $rate->remaining.' '.$currency;
        $dueDate = !empty($rate->due_date) ? Carbon::parse($rate->due_date)->toDateString() : Carbon::now()->toDateString();

        return [
            'messaging_product' => 'whatsapp',
            'to' => $receiver,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => 'en'],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $amount],
                            ['type' => 'text', 'text' => $dueDate],
                        ],
                    ],
                ],
            ],
        ];
    }
}
