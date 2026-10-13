<?php

namespace App\Plugins\WhatsappMessaging;

use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappMessageNotSentException;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use App\Plugins\WhatsappMessaging\Services\WhatsappCloudApiClient;
use App\Plugins\WhatsappMessaging\Services\WhatsappMessageService;
use App\Plugins\WhatsappMessaging\Services\WhatsappOutboundMessage;
use Illuminate\Support\Facades\Log;

class WhatsappMessagingGateway {
    public function __construct(
        private WhatsappCloudApiClient $cloudApiClient,
        private WhatsappMessageService $whatsappMessageService,
    ) {}

    /**
     * Sends once and does not retry: SmsProcessor runs a single attempt, and retry or SMS fallback is decided by the caller from WhatsappMessageNotSentException::isRetryable().
     *
     * @throws WhatsappMessageNotSentException
     */
    public function sendSms(WhatsappOutboundMessage $message, Sms $sms, WhatsappContact $contact): void {
        try {
            $wamid = $this->cloudApiClient->sendMessage($message);
        } catch (WhatsappMessageNotSentException $exception) {
            $this->whatsappMessageService->create($this->messageAttributes($message, $sms, $contact) + [
                'status' => WhatsappMessage::STATUS_FAILED,
                'error_code' => $exception->metaErrorCode ?: null,
                'error_message' => $exception->getMessage(),
                'failed_at' => now(),
            ]);
            Log::error('WhatsApp message sending failed', [
                'sms_id' => $sms->id,
                'meta_error_code' => $exception->metaErrorCode,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->whatsappMessageService->create($this->messageAttributes($message, $sms, $contact) + [
            'wamid' => $wamid,
            'status' => WhatsappMessage::STATUS_ACCEPTED,
        ]);
        Log::info('WhatsApp message accepted by Meta', ['sms_id' => $sms->id, 'wamid' => $wamid]);
    }

    /**
     * @return array<string, mixed>
     */
    private function messageAttributes(WhatsappOutboundMessage $message, Sms $sms, WhatsappContact $contact): array {
        return [
            'sms_id' => $sms->id,
            'whatsapp_contact_id' => $contact->id,
            'message_type' => $message->isTemplate() ? WhatsappMessage::MESSAGE_TYPE_TEMPLATE : WhatsappMessage::MESSAGE_TYPE_TEXT,
            'template_name' => $message->templateName,
            'template_language' => $message->templateLanguage,
        ];
    }
}
