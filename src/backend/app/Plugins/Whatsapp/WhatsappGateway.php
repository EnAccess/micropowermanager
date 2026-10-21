<?php

namespace App\Plugins\Whatsapp;

use App\Models\Address\Address;
use App\Models\Person\Person;
use App\Models\Sms;
use App\Plugins\Whatsapp\Exceptions\MessageNotSentException;
use App\Plugins\Whatsapp\Models\WhatsappCredential;
use App\Plugins\Whatsapp\Services\WhatsappClient;
use App\Plugins\Whatsapp\Services\WhatsappCredentialService;
use App\Plugins\Whatsapp\Services\WhatsappMessageService;
use App\Services\SmsGatewayResolverService;
use Illuminate\Support\Facades\Log;

class WhatsappGateway {
    public function __construct(
        private WhatsappCredentialService $credentialService,
        private WhatsappMessageService $messageService,
        private WhatsappClient $client,
    ) {}

    public function sendSms(string $body, string $phoneNumber, Sms $registeredSms): void {
        $credentials = $this->credentialService->getCredentials();

        try {
            $phone = preg_replace('/\D/', '', $phoneNumber);
            if ($phone === '') {
                throw new MessageNotSentException('Invalid phone number');
            }
            if (!$credentials || !$credentials->access_token || !$credentials->phone_number_id) {
                throw new MessageNotSentException('WhatsApp credentials are not configured');
            }

            $template = $this->templateFor($registeredSms, $credentials);
            $result = $this->client->sendTemplate(
                $credentials,
                $phone,
                $template,
                $credentials->template_language,
                [$this->recipientName($registeredSms), $body]
            );

            if (!$result['ok']) {
                throw new MessageNotSentException('WhatsApp send failed: '.$result['error']);
            }

            $this->messageService->create([
                'sms_id' => $registeredSms->id,
                'message_id' => $result['message_id'],
                'template_name' => $template,
                'status' => 'sent',
            ]);
        } catch (\Throwable $e) {
            $this->fallbackOrThrow($e, $credentials, $registeredSms);
        }
    }

    private function templateFor(Sms $sms, WhatsappCredential $c): string {
        $type = strtolower((string) $sms->trigger_type);

        $isReminder = str_contains($type, 'rate') || str_contains($type, 'appliance');

        return $isReminder ? $c->reminder_template : $c->receipt_template;
    }

    /**
     * First name of the customer who owns the receiving phone number.
     * Falls back to a neutral greeting when no customer matches.
     */
    private function recipientName(Sms $sms): string {
        try {
            $digits = preg_replace('/\D/', '', (string) $sms->receiver);
            $address = Address::query()
                ->whereIn('phone', [(string) $sms->receiver, $digits, '+'.$digits])
                ->with('owner')
                ->first();
            $owner = $address?->owner;
            $name = $owner instanceof Person ? trim((string) $owner->name) : '';
        } catch (\Throwable) {
            return 'customer';
        }

        return $name !== '' ? $name : 'customer';
    }

    private function fallbackOrThrow(\Throwable $error, ?WhatsappCredential $c, Sms $sms): void {
        Log::warning('WhatsApp send failed', ['sms_id' => $sms->id, 'error' => $error->getMessage()]);

        $resolver = resolve(SmsGatewayResolverService::class);
        $fallback = ($c->fallback_to_sms ?? true) ? $resolver->getFallbackGatewayName() : null;

        if ($fallback !== null) {
            try {
                $r = $resolver->resolveGatewayAndArgs($fallback, $sms);
                $r['gateway']->sendSms(...$r['args']);
                $this->messageService->create([
                    'sms_id' => $sms->id,
                    'message_id' => null,
                    'template_name' => null,
                    'status' => 'fallback_sms',
                ]);

                return;
            } catch (\Throwable $fallbackError) {
                Log::error('SMS fallback also failed', ['error' => $fallbackError->getMessage()]);
            }
        }

        // No fallback worked: record the failure so it shows in the WhatsApp message log,
        // then throw so SmsProcessor marks the Sms as failed and sms:resend-rejected retries it.
        $this->messageService->create([
            'sms_id' => $sms->id,
            'message_id' => null,
            'template_name' => null,
            'status' => 'failed',
        ]);

        throw $error instanceof MessageNotSentException ? $error : new MessageNotSentException('WhatsApp message sending failed: '.$error->getMessage(), 0, $error);
    }
}
