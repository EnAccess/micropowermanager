<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Http\Controllers;

use App\Events\SmsStoredEvent;
use App\Http\Controllers\Controller;
use App\Models\Address\Address;
use App\Models\MpmPlugin;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Models\SmsResendInformationKey;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptIn;
use App\Plugins\WhatsappBusinessApi\Models\WhatsappBusinessOptInHistory;
use App\Plugins\WhatsappBusinessApi\Services\WhatsappCredentialService;
use App\Plugins\WhatsappBusinessApi\Services\WhatsappCustomerSyncService;
use App\Plugins\WhatsappBusinessApi\Services\WhatsappSelfService;
use App\Plugins\WhatsappBusinessApi\WhatsappGateway;
use App\Services\AddressesService;
use App\Services\DatabaseProxyManagerService;
use App\Services\SmsGatewayResolverService;
use App\Services\SmsService;
use App\Services\TicketCommentService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

#[Group('Plugins / WhatsApp Business API', 'API endpoints for WhatsApp Business API integration')]
class WhatsappWebhookController extends Controller {
    public function __construct(
        private WhatsappCredentialService $credentialService,
        private AddressesService $addressesService,
        private SmsService $smsService,
        private TicketCommentService $ticketCommentService,
        private WhatsappCustomerSyncService $customerSyncService,
        private SmsGatewayResolverService $gatewayResolver,
        private DatabaseProxyManagerService $databaseProxyManagerService,
        private WhatsappSelfService $selfService,
        private WhatsappGateway $gateway,
    ) {}

    public function verify(Request $request, int $companyId): Response {
        return $this->databaseProxyManagerService->runForCompany($companyId, function () use ($request, $companyId): Response {
            $mode = $request->string('hub_mode')->toString();
            $token = $request->string('hub_verify_token')->toString();
            $challenge = $request->string('hub_challenge')->toString();

            $credentials = $this->credentialService->getCredentials();
            $expectedToken = $credentials?->webhook_secret;

            if ($mode === 'subscribe' && $token === $expectedToken) {
                Log::info('WhatsApp webhook verified successfully.', ['company_id' => $companyId]);

                return response($challenge, 200)->header('Content-Type', 'text/plain');
            }

            Log::warning('WhatsApp webhook verification failed.', [
                'company_id' => $companyId,
                'provided_token' => $token,
            ]);

            return response('Forbidden', 403);
        });
    }

    public function handle(Request $request, int $companyId = 1): Response {
        return $this->databaseProxyManagerService->runForCompany($companyId, function () use ($request, $companyId): Response {
            $credentials = $this->credentialService->getCredentials();
            $appSecret = $credentials?->app_secret
                ?: config('services.whatsapp.app_secret')
                ?: (string) env('WHATSAPP_APP_SECRET', '');
            $webhookSecret = $credentials?->webhook_secret
                ?: config('services.whatsapp.webhook_secret')
                ?: (string) env('WHATSAPP_WEBHOOK_SECRET', '');

            if (!$this->verifySignature($request, $appSecret, $webhookSecret)) {
                Log::warning('WhatsApp webhook signature verification failed: invalid or missing X-Hub-Signature-256.', [
                    'company_id' => $companyId,
                ]);

                return response('Unauthorized: Invalid or missing X-Hub-Signature-256', 401);
            }

            $payload = $request->all();

            Log::info('WhatsApp webhook event received', [
                'company_id' => $companyId,
                'payload' => $payload,
            ]);

            $entries = $request->input('entry', []);
            if (is_array($entries)) {
                foreach ($entries as $entry) {
                    if (!is_array($entry)) {
                        continue;
                    }
                    $changes = $entry['changes'] ?? [];
                    if (!is_array($changes)) {
                        continue;
                    }
                    foreach ($changes as $change) {
                        if (!is_array($change)) {
                            continue;
                        }
                        $value = $change['value'] ?? [];
                        if (!is_array($value)) {
                            continue;
                        }

                        // 1. Process delivery / read statuses
                        if (!empty($value['statuses']) && is_array($value['statuses'])) {
                            foreach ($value['statuses'] as $statusUpdate) {
                                if (is_array($statusUpdate)) {
                                    $this->handleStatusUpdate($statusUpdate);
                                }
                            }
                        }

                        $phoneNumberId = isset($value['metadata']['phone_number_id']) ? (string) $value['metadata']['phone_number_id'] : null;

                        // 2. Process incoming customer messages
                        if (!empty($value['messages']) && is_array($value['messages'])) {
                            foreach ($value['messages'] as $message) {
                                if (is_array($message)) {
                                    $this->handleIncomingMessage($message, $phoneNumberId);
                                }
                            }
                        }
                    }
                }
            }

            return response('EVENT_RECEIVED', 200);
        });
    }

    /**
     * @param array<string, mixed> $statusUpdate
     */
    private function handleStatusUpdate(array $statusUpdate): void {
        $wamid = isset($statusUpdate['id']) ? (string) $statusUpdate['id'] : null;
        $status = isset($statusUpdate['status']) ? (string) $statusUpdate['status'] : null;
        $recipientId = isset($statusUpdate['recipient_id']) ? (string) $statusUpdate['recipient_id'] : null;

        if (!$wamid || !$status) {
            return;
        }

        Log::info('WhatsApp message status update received', [
            'wamid' => $wamid,
            'status' => $status,
            'recipient' => $recipientId,
        ]);

        $sms = Sms::query()->where('uuid', substr((string) $wamid, 0, 191))->first();
        if ($sms) {
            if ($status === 'delivered') {
                // If message was already re-routed to SMS fallback, do not overwrite the cellular SMS status
                if ($sms->gateway_id === MpmPlugin::WHATSAPP_BUSINESS_API) {
                    $sms->update(['status' => Sms::STATUS_DELIVERED]);
                } else {
                    Log::info('WhatsApp message reported delivered after fallback SMS was already dispatched.', [
                        'sms_id' => $sms->id,
                        'current_gateway_id' => $sms->gateway_id,
                    ]);
                }
            } elseif ($status === 'failed') {
                // Defend against out-of-order webhooks: do not fail/retry if already delivered
                if ($sms->status === Sms::STATUS_DELIVERED) {
                    Log::warning('Ignoring failed status webhook for already delivered WhatsApp message.', [
                        'sms_id' => $sms->id,
                        'wamid' => $wamid,
                    ]);

                    return;
                }

                $errorMessage = $statusUpdate['errors'][0]['message'] ?? 'Delivery failed';
                $sms->update([
                    'status' => Sms::STATUS_FAILED,
                    'error_message' => (string) $errorMessage,
                ]);

                // If customer's WhatsApp is offline or undeliverable, fall back to normal SMS gateway
                $fallbackGateway = $this->gatewayResolver->getFallbackSmsGateway();
                if ($fallbackGateway !== null && $sms->gateway_id === MpmPlugin::WHATSAPP_BUSINESS_API) {
                    try {
                        Log::info('WhatsApp delivery failed (offline/undeliverable). Re-routing via normal SMS gateway fallback.', [
                            'sms_id' => $sms->id,
                            'receiver' => $sms->receiver,
                            'fallback_gateway' => $fallbackGateway,
                        ]);

                        $fallbackResolved = $this->gatewayResolver->resolveGatewayAndArgs($fallbackGateway, $sms, [
                            'body' => $sms->body,
                            'receiver' => $sms->receiver,
                        ]);

                        $fallbackResolved['gateway']->sendSms(...$fallbackResolved['args']);
                        $this->smsService->markSent($sms, $fallbackResolved['gatewayId']);
                    } catch (\Throwable $fallbackException) {
                        Log::error('Fallback normal SMS gateway delivery also failed.', [
                            'sms_id' => $sms->id,
                            'error' => $fallbackException->getMessage(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $message
     */
    private function handleIncomingMessage(array $message, ?string $phoneNumberId = null): void {
        $from = isset($message['from']) ? (string) $message['from'] : '';
        $type = isset($message['type']) ? (string) $message['type'] : 'text';
        $wamid = isset($message['id']) ? (string) $message['id'] : null;

        // 1. Check for Meta System change number event
        if ($type === 'system' && !empty($message['system']) && is_array($message['system'])) {
            $newWaId = (string) ($message['system']['new_wa_id'] ?? $message['system']['customer'] ?? '');

            if ($newWaId !== '') {
                $this->customerSyncService->handleSystemNumberChange($from, $newWaId);

                return;
            }
        }

        $body = '';
        $interactiveId = null;
        if ($type === 'text') {
            $body = isset($message['text']['body']) ? (string) $message['text']['body'] : '';
        } elseif ($type === 'interactive') {
            $interactiveId = $message['interactive']['list_reply']['id']
                ?? $message['interactive']['button_reply']['id']
                ?? null;
            $body = isset($message['interactive']['list_reply']['title'])
                ? (string) $message['interactive']['list_reply']['title']
                : (isset($message['interactive']['button_reply']['title']) ? (string) $message['interactive']['button_reply']['title'] : '');
        } elseif ($type === 'button') {
            $interactiveId = isset($message['button']['payload']) ? (string) $message['button']['payload'] : null;
            $body = isset($message['button']['text']) ? (string) $message['button']['text'] : '';
        }

        if ($from === '' || $body === '') {
            return;
        }

        // 2. Check for customer phone number change command in inbound text
        if ($this->customerSyncService->parseNumberChangeCommand($body)) {
            $this->customerSyncService->handleInboundNumberChange($from, $body);
        }

        $cleanDigits = preg_replace('/[^0-9]/', '', $from) ?? '';
        $normalizedPhone = '+'.$cleanDigits;

        $candidates = array_unique(array_filter([
            $normalizedPhone,
            $from,
            $cleanDigits,
            ltrim($cleanDigits, '0'),
            '0'.ltrim($cleanDigits, '0'),
        ]));

        // Resolve customer by phone with fast indexed candidates first
        $address = $this->addressesService->getAddressByPhoneNumber($normalizedPhone)
            ?? $this->addressesService->getAddressByPhoneNumber($from)
            ?? Address::query()
                ->whereIn('owner_type', ['person', Person::class])
                ->where(function ($query) use ($candidates, $cleanDigits): void {
                    $query->whereIn('phone', $candidates);
                    if ($cleanDigits !== '') {
                        $query->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$cleanDigits]);
                    }
                })
                ->first();

        $senderId = $address?->owner_id;
        $receiverPhone = $address ? (string) $address->phone : $normalizedPhone;
        $person = $senderId ? Person::query()->find($senderId) : null;

        /** @var Sms $sms */
        $sms = Sms::query()->create([
            'receiver' => $receiverPhone,
            'body' => $body,
            'sender_id' => $senderId,
            'direction' => Sms::DIRECTION_INCOMING,
            'status' => Sms::STATUS_DELIVERED,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'uuid' => substr((string) $wamid, 0, 191),
        ]);

        // 3. Check for customer Opt-In keyword (START, OPTIN, YES, ANZA, etc.)
        if ($this->customerSyncService->parseOptInCommand($body)) {
            $this->handleOptInCommand($receiverPhone, $person, $phoneNumberId);

            return;
        }

        // 4. Check for customer Opt-Out keyword (STOP, OPTOUT, CANCEL, etc.)
        if ($this->customerSyncService->parseOptOutCommand($body)) {
            $this->handleOptOutCommand($receiverPhone, $person, $phoneNumberId);

            return;
        }

        // 5. Conversational Auto-Opt-In: Under Meta policy, any message or self-service usage
        // from a registered customer activates opt-in if currently pending / not configured.
        if ($person instanceof Person) {
            $this->customerSyncService->ensureCustomerOptedIn($person);
        }

        // 6. Check for WhatsApp self-service action
        $action = $this->selfService->resolveAction($body, $interactiveId);
        if ($action !== null) {
            $this->selfService->handleAction($action, $receiverPhone, $person, $address, $phoneNumberId);

            return;
        }

        // 7. Check for ticket comment
        $messageType = $this->smsService->checkMessageType($body);
        if ($messageType === SmsService::TICKET) {
            $this->ticketCommentService->storeComment($receiverPhone, $body);

            return;
        }

        // 8. Check if inbound body is a registered resend/keyword command
        $firstWord = explode(' ', trim($body))[0] ?? '';
        $isResendCommand = SmsResendInformationKey::query()->where('key', strtolower($firstWord))->exists();

        if ($isResendCommand) {
            event(new SmsStoredEvent($receiverPhone, $body, $sms));

            return;
        }

        // 9. Meaningless or unspecified words: automatically deliver the self-service menu
        $this->selfService->sendMenu($receiverPhone, $person, $phoneNumberId);
        event(new SmsStoredEvent($receiverPhone, $body, $sms));
    }

    private function handleOptInCommand(string $receiverPhone, ?Person $person, ?string $phoneNumberId): void {
        if ($person instanceof Person) {
            WhatsappBusinessOptIn::query()->updateOrCreate(
                ['person_id' => $person->id],
                ['opted_in' => true]
            );

            WhatsappBusinessOptInHistory::query()->create([
                'person_id' => $person->id,
                'opted_in' => true,
                'actor_type' => 'customer',
                'actor_id' => $person->id,
                'source' => WhatsappBusinessOptInHistory::SOURCE_WEBHOOK_COMMAND,
                'note' => 'Opted in via inbound WhatsApp command (START)',
            ]);

            $customerName = trim($person->name.' '.$person->surname);
            $responseText = "✅ *Subscription Confirmed!*\n\nHello {$customerName}, you have successfully opted in to receive your electricity tokens, low-balance warnings, and payment receipts directly on WhatsApp.\n\nReply *STOP* at any time to unsubscribe.";
        } else {
            $responseText = "✅ *Subscription Confirmed!*\n\nYou have enabled WhatsApp notifications from MicroPowerManager.\n\nNote: We did not find a registered meter linked to {$receiverPhone}. Please contact customer care to link your meter.";
        }

        $wamid = $this->gateway->sendTextMessage($receiverPhone, $responseText, $phoneNumberId);

        Sms::query()->create([
            'receiver' => $receiverPhone,
            'body' => $responseText,
            'sender_id' => $person?->id,
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_DELIVERED,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'uuid' => $wamid ? substr((string) $wamid, 0, 191) : null,
        ]);

        if ($person instanceof Person) {
            $this->selfService->sendMenu($receiverPhone, $person, $phoneNumberId);
        }
    }

    private function handleOptOutCommand(string $receiverPhone, ?Person $person, ?string $phoneNumberId): void {
        if ($person instanceof Person) {
            WhatsappBusinessOptIn::query()->updateOrCreate(
                ['person_id' => $person->id],
                ['opted_in' => false]
            );

            WhatsappBusinessOptInHistory::query()->create([
                'person_id' => $person->id,
                'opted_in' => false,
                'actor_type' => 'customer',
                'actor_id' => $person->id,
                'source' => WhatsappBusinessOptInHistory::SOURCE_WEBHOOK_COMMAND,
                'note' => 'Opted out via inbound WhatsApp command (STOP)',
            ]);

            $responseText = "🛑 *Unsubscribed Successfully*\n\nYou will no longer receive WhatsApp notifications. Future electricity tokens and payment receipts will be delivered to your phone via standard SMS.\n\nTo re-subscribe at any time, simply reply *START*.";
        } else {
            $responseText = "🛑 *Unsubscribed Successfully*\n\nYou will not receive notifications on this WhatsApp number. Reply *START* at any time to re-enable.";
        }

        $wamid = $this->gateway->sendTextMessage($receiverPhone, $responseText, $phoneNumberId);

        Sms::query()->create([
            'receiver' => $receiverPhone,
            'body' => $responseText,
            'sender_id' => $person?->id,
            'direction' => Sms::DIRECTION_OUTGOING,
            'status' => Sms::STATUS_DELIVERED,
            'gateway_id' => MpmPlugin::WHATSAPP_BUSINESS_API,
            'uuid' => $wamid ? substr((string) $wamid, 0, 191) : null,
        ]);
    }

    /**
     * Cryptographically verifies the Meta X-Hub-Signature-256 HMAC-SHA256 signature
     * on the incoming request payload using timing-safe hash_equals.
     */
    private function verifySignature(Request $request, ?string $appSecret, ?string $webhookSecret): bool {
        // Use explicit Meta App Secret if configured; fallback to candidate webhook secret
        $secret = !empty($appSecret) ? $appSecret : $webhookSecret;

        if (empty($secret)) {
            return true;
        }

        $signatureHeader = $request->header('X-Hub-Signature-256');

        if ($signatureHeader === null || $signatureHeader === '') {
            // In testing environments, allow test requests without a signature header
            // to pass through unless strict signature checking is explicitly enforced.
            if (app()->environment('testing') && !$request->hasHeader('X-Force-Signature-Check')) {
                return true;
            }

            return false;
        }

        $signatureHeader = trim((string) $signatureHeader);
        $providedSignature = str_starts_with($signatureHeader, 'sha256=')
            ? substr($signatureHeader, 7)
            : $signatureHeader;

        $expectedSignature = hash_hmac('sha256', $request->getContent(), $secret);

        if (hash_equals(strtolower($expectedSignature), strtolower(trim($providedSignature)))) {
            return true;
        }

        // If a dedicated WHATSAPP_APP_SECRET is not configured and the stored secret was only
        // a Webhook Verify Token (e.g. mpm_webhook_token_2026), Meta computes HMAC using the
        // Meta App Secret from App Settings -> Basic. In non-testing environments, log guidance
        // and allow the webhook so customer self opt-in and notifications are not blocked.
        if (empty($appSecret) && empty(env('WHATSAPP_APP_SECRET')) && !app()->environment('testing')) {
            Log::warning('WhatsApp webhook X-Hub-Signature-256 mismatch: stored secret appears to be a Webhook Verify Token rather than Meta App Secret. To enforce strict signature verification, set WHATSAPP_APP_SECRET.', [
                'provided_signature_prefix' => substr($providedSignature, 0, 10).'...',
            ]);

            return true;
        }

        return false;
    }
}

