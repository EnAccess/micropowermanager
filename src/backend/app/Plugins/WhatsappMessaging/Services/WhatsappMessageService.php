<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Models\SmsAttempt;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\Http\Clients\WhatsappApiClient;
use App\Plugins\WhatsappMessaging\Models\WhatsappContact;
use App\Plugins\WhatsappMessaging\Models\WhatsappCredential;
use App\Services\SmsService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WhatsappMessageService {
    public function __construct(private SmsAttempt $smsAttempt, private WhatsappApiClient $apiClient, private WhatsappTemplateService $templateService) {}

    /**
     * Outside the window Meta only accepts a template (error 131047). Every attempt is logged, so SMS History shows why SMS took over.
     * Inside the window even a template message goes as its filled-in text, which Meta charges as a service message instead of a template.
     *
     * @param array<string, string> $values the body parser's values, or the operator's values for $templateId
     */
    public function send(WhatsappCredential $credential, Sms $sms, WhatsappContact $contact, ?string $bodyParserReference, array $values, ?string $templateId): void {
        $isWindowOpen = $contact->isCustomerServiceWindowOpen();

        try {
            $content = match (true) {
                $isWindowOpen => ['type' => 'text', 'text' => ['body' => $sms->body]],
                $templateId !== null => $this->templateService->manualTemplate($templateId)->message($values),
                default => $this->templateService->notificationMessage($bodyParserReference, $values),
            };
        } catch (WhatsappTemplateNotConfiguredException $exception) {
            $this->logAttempt($sms, ['message_type' => SmsAttempt::MESSAGE_TYPE_TEMPLATE], $exception);

            throw $exception;
        }

        $this->sendContent($credential, $sms, $contact, $content);
    }

    /** @throws WhatsappApiException|WhatsappTemplateNotConfiguredException */
    public function resend(WhatsappCredential $credential, Sms $sms, WhatsappContact $contact): void {
        $lastAttempt = $sms->deliveryAttempts()->where('gateway_id', MpmPlugin::WHATSAPP_MESSAGING)->whereNotNull('request')->latest('id')->first()
            ?? throw new WhatsappTemplateNotConfiguredException('There is no earlier WhatsApp message to send again.');

        $this->sendContent($credential, $sms, $contact, (array) $lastAttempt->request);
    }

    /** @param array<string, mixed> $status one webhook statuses[] item */
    public function applyStatus(array $status): void {
        DB::connection('tenant')->transaction(function () use ($status): void {
            $attempt = $this->smsAttempt->newQuery()->with('sms')
                ->where('gateway_id', MpmPlugin::WHATSAPP_MESSAGING)->where('external_id', $status['id'] ?? null)
                ->lockForUpdate()->first();
            $newStatus = (string) ($status['status'] ?? '');
            if (!$attempt instanceof SmsAttempt || !$attempt->canMoveTo($newStatus)) {
                return;
            }

            $error = $status['errors'][0] ?? [];
            $attempt->fill(['status' => $newStatus, $newStatus.'_at' => Carbon::createFromTimestamp((int) $status['timestamp']), 'error_code' => $error['code'] ?? null, 'error_message' => $error['title'] ?? null])->save();

            // Once the SMS fallback has taken the row, WhatsApp events no longer describe it.
            $sms = $attempt->sms;
            if (!$sms instanceof Sms || (int) $sms->gateway_id !== MpmPlugin::WHATSAPP_MESSAGING || (int) $sms->status !== Sms::STATUS_SENT) {
                return;
            }

            if ($newStatus === SmsAttempt::STATUS_FAILED) {
                // Resolved here, not injected: SmsService depends on the WhatsApp gateway, which depends on this service.
                app()->make(SmsService::class)->whatsappFailed(
                    $sms,
                    sprintf('WhatsApp: %s (%s)', $error['title'] ?? 'Unknown error', $error['code'] ?? 'no code'),
                    WhatsappApiException::isTemporaryCode((int) ($error['code'] ?? 0)),
                );
            } elseif ($newStatus !== SmsAttempt::STATUS_SENT) {
                $sms->update(['status' => Sms::STATUS_DELIVERED]);
            }
        });
    }

    /** @param array<string, mixed> $content */
    private function sendContent(WhatsappCredential $credential, Sms $sms, WhatsappContact $contact, array $content): void {
        $attributes = [
            'message_type' => $content['type'] === 'template' ? SmsAttempt::MESSAGE_TYPE_TEMPLATE : SmsAttempt::MESSAGE_TYPE_TEXT,
            'template_name' => $content['template']['name'] ?? null,
            'request' => $content,
        ];

        try {
            $wamid = $this->apiClient->sendMessage($credential, $contact->phone, $content);
        } catch (WhatsappApiException $exception) {
            $this->logAttempt($sms, $attributes, $exception);

            throw $exception;
        }

        $this->logAttempt($sms, [...$attributes, 'external_id' => $wamid], null);
    }

    /** @param array<string, mixed> $attributes */
    private function logAttempt(Sms $sms, array $attributes, ?\Throwable $exception): void {
        $this->smsAttempt->newQuery()->create([
            ...$attributes,
            'sms_id' => $sms->id,
            'gateway_id' => MpmPlugin::WHATSAPP_MESSAGING,
            'status' => $exception instanceof \Throwable ? SmsAttempt::STATUS_FAILED : SmsAttempt::STATUS_ACCEPTED,
            'error_code' => $exception instanceof \Throwable ? ($exception->getCode() ?: null) : null,
            'error_message' => $exception?->getMessage(),
            'failed_at' => $exception instanceof \Throwable ? now() : null,
        ]);
    }
}
