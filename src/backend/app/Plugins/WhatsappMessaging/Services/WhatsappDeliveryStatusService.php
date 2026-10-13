<?php

namespace App\Plugins\WhatsappMessaging\Services;

use App\Console\Commands\ResendRejectedMessages;
use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Models\WhatsappMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WhatsappDeliveryStatusService {
    private const array TIMESTAMP_COLUMNS = [
        WhatsappMessage::STATUS_SENT => 'sent_at',
        WhatsappMessage::STATUS_DELIVERED => 'delivered_at',
        WhatsappMessage::STATUS_READ => 'read_at',
    ];

    public function __construct(
        private WhatsappMessage $whatsappMessage,
        private WhatsappCredentialService $credentialService,
    ) {}

    /**
     * Meta neither orders nor de-duplicates status events, so every update only moves a message forward.
     * A late failure is not resent here: answering Meta must stay fast, and sms:resend-rejected picks the failed sms row up.
     *
     * @param array<string, mixed> $status one item of a webhook's value.statuses[]
     */
    public function apply(array $status): void {
        DB::connection('tenant')->transaction(function () use ($status): void {
            $message = $this->whatsappMessage->newQuery()
                ->where('wamid', $status['id'] ?? null)
                ->lockForUpdate()
                ->first();

            // Messages that are not tied to an sms row, such as opt-in confirmations, are not tracked.
            if (!$message instanceof WhatsappMessage) {
                return;
            }

            $occurredAt = Carbon::createFromTimestamp((int) $status['timestamp']);
            $pricingCategory = $status['pricing']['category'] ?? null;
            if (is_string($pricingCategory)) {
                $message->pricing_category = $pricingCategory;
            }

            $metaStatus = (string) ($status['status'] ?? '');
            if ($metaStatus === WhatsappMessage::STATUS_FAILED) {
                $this->applyFailure($message, $status['errors'][0] ?? [], $occurredAt);
            } elseif (array_key_exists($metaStatus, self::TIMESTAMP_COLUMNS)) {
                $this->applyProgress($message, $metaStatus, $occurredAt);
            }

            $message->save();
        });
    }

    private function applyProgress(WhatsappMessage $message, string $newStatus, Carbon $occurredAt): void {
        if ($message->status === WhatsappMessage::STATUS_FAILED || $message->hasReached($newStatus)) {
            return;
        }

        $message->status = $newStatus;
        $message->{self::TIMESTAMP_COLUMNS[$newStatus]} = $occurredAt;

        if ($message->hasReached(WhatsappMessage::STATUS_DELIVERED)) {
            $this->smsSentByWhatsapp($message)?->update(['status' => Sms::STATUS_DELIVERED]);
        }
    }

    /**
     * @param array<string, mixed> $error the first item of the status' errors[]
     */
    private function applyFailure(WhatsappMessage $message, array $error, Carbon $occurredAt): void {
        // A repeated failed event must not fail the sms row again once the SMS fallback has sent it.
        if ($message->status === WhatsappMessage::STATUS_FAILED || $message->hasReached(WhatsappMessage::STATUS_DELIVERED)) {
            return;
        }

        $code = isset($error['code']) ? (int) $error['code'] : null;
        $title = (string) ($error['title'] ?? $error['message'] ?? 'Unknown error');
        $details = $error['error_data']['details'] ?? null;

        $message->status = WhatsappMessage::STATUS_FAILED;
        $message->failed_at = $occurredAt;
        $message->error_code = $code;
        $message->error_message = is_string($details) && $details !== '' && $details !== $title ? $title.': '.$details : $title;

        $sms = $this->smsSentByWhatsapp($message);
        if (!$sms instanceof Sms) {
            return;
        }

        $smsUpdate = [
            'status' => Sms::STATUS_FAILED,
            'error_message' => sprintf('WhatsApp: %s (%s)', $title, $code ?? 'no code'),
        ];

        // With fallback disabled the operator chose WhatsApp only, so sms:resend-rejected must not send this row by SMS.
        if (!$this->credentialService->isSmsFallbackEnabled()) {
            $smsUpdate['attempts'] = ResendRejectedMessages::MAX_ATTEMPTS;
        }

        $sms->update($smsUpdate);
    }

    /**
     * Once the SMS fallback has sent a row, its gateway is the SMS gateway and WhatsApp events no longer describe it.
     */
    private function smsSentByWhatsapp(WhatsappMessage $message): ?Sms {
        $sms = $message->sms;

        return $sms instanceof Sms && (int) $sms->gateway_id === MpmPlugin::WHATSAPP_MESSAGING ? $sms : null;
    }
}
