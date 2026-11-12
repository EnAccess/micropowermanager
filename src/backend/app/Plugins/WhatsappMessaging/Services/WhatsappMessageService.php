<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WhatsappMessageService {
    // Meta allows free text for 24 hours after the customer's last message; the 10 spare minutes cover time in the queue.
    private const int CUSTOMER_SERVICE_WINDOW_MINUTES = 23 * 60 + 50;

    public function __construct(private WhatsappMessage $whatsappMessage, private WhatsappApiClient $apiClient, private WhatsappTemplateService $templateService) {}

    /**
     * Outside the window Meta only accepts a template (error 131047). Every attempt is logged, so the log shows why SMS took over.
     *
     * @param array<string, string> $values
     */
    public function send(WhatsappCredential $credential, Sms $sms, WhatsappContact $contact, string $body, ?string $reference, array $values): void {
        $isWindowOpen = $contact->last_inbound_at?->isAfter(now()->subMinutes(self::CUSTOMER_SERVICE_WINDOW_MINUTES)) ?? false;
        $message = $this->whatsappMessage->newInstance([
            'sms_id' => $sms->id,
            'whatsapp_contact_id' => $contact->id,
            'message_type' => $isWindowOpen ? WhatsappMessage::MESSAGE_TYPE_TEXT : WhatsappMessage::MESSAGE_TYPE_TEMPLATE,
        ]);
        try {
            $content = $isWindowOpen ? ['type' => 'text', 'text' => ['body' => $body]] : $this->templateService->message($reference, $values);
            $wamid = $this->apiClient->sendMessage($credential, $contact->phone, $content);
            $message->fill(['template_name' => $content['template']['name'] ?? null, 'wamid' => $wamid, 'status' => WhatsappMessage::STATUS_ACCEPTED])->save();
        } catch (WhatsappApiException|WhatsappTemplateNotConfiguredException $exception) {
            $message->fill(['status' => WhatsappMessage::STATUS_FAILED, 'error_code' => $exception->getCode() ?: null, 'error_message' => $exception->getMessage(), 'failed_at' => now()])->save();
            throw $exception;
        }
    }

    /** @param array<string, mixed> $status one webhook statuses[] item; a failure marks the sms row failed, so sms:resend-rejected sends it by SMS */
    public function applyStatus(array $status): void {
        DB::connection('tenant')->transaction(function () use ($status): void {
            $message = $this->whatsappMessage->newQuery()->with('sms')->where('wamid', $status['id'] ?? null)->lockForUpdate()->first();
            $newStatus = (string) ($status['status'] ?? '');
            if (!$message instanceof WhatsappMessage || !$message->canMoveTo($newStatus)) {
                return;
            }
            $error = $status['errors'][0] ?? [];
            $message->fill(['status' => $newStatus, $newStatus.'_at' => Carbon::createFromTimestamp((int) $status['timestamp']), 'error_code' => $error['code'] ?? null, 'error_message' => $error['title'] ?? null])->save();
            // Once the SMS fallback has sent the row, its gateway is the SMS gateway and WhatsApp events no longer describe it.
            if ($newStatus !== WhatsappMessage::STATUS_SENT && (int) $message->sms?->gateway_id === MpmPlugin::WHATSAPP_MESSAGING) {
                $message->sms->update($newStatus === WhatsappMessage::STATUS_FAILED ? ['status' => Sms::STATUS_FAILED, 'error_message' => sprintf('WhatsApp: %s (%s)', $error['title'] ?? 'Unknown error', $error['code'] ?? 'no code')] : ['status' => Sms::STATUS_DELIVERED]);
            }
        });
    }
}
