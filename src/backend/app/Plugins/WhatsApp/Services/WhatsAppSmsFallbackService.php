<?php

namespace App\Plugins\WhatsApp\Services;

use App\Models\ApplianceRate;
use App\Models\Sms;
use App\Models\Transaction\Transaction;
use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp is the primary channel; this service keeps the notification promise
 * when WhatsApp cannot prove delivery. It reuses the existing SMS senders and
 * re-reads the same persisted models, so tokens are never regenerated and
 * amounts are never invented.
 */
class WhatsAppSmsFallbackService {
    /** @var array<int, string> */
    private const array FALLBACK_TYPES = [
        WhatsAppMessage::TYPE_PAYMENT_RECEIPT,
        WhatsAppMessage::TYPE_TOKEN_DELIVERY,
        WhatsAppMessage::TYPE_LOW_BALANCE_WARNING,
    ];

    public function __construct(
        private WhatsAppMessage $messages,
        private SmsService $smsService,
    ) {}

    /**
     * @return array{sent: int, skipped: int}
     */
    public function sendPending(int $limit = 100): array {
        if (!(bool) config('whatsapp.sms_fallback.enabled', true)) {
            return ['sent' => 0, 'skipped' => 0];
        }

        $timeoutCutoff = now()->subMinutes((int) config('whatsapp.sms_fallback.timeout_minutes', 5));
        $maxAttempts = (int) config('whatsapp.sms_fallback.max_attempts', 3);

        $messages = $this->messages->newQuery()
            ->whereNull('fallback_at')
            ->where('fallback_attempts', '<', $maxAttempts)
            ->whereIn('notification_type', self::FALLBACK_TYPES)
            ->where(function (Builder $query) use ($timeoutCutoff): void {
                $query->where('status', WhatsAppMessage::STATUS_FAILED)
                    ->orWhere(function (Builder $undelivered) use ($timeoutCutoff): void {
                        $undelivered->whereIn('status', [
                            WhatsAppMessage::STATUS_QUEUED,
                            WhatsAppMessage::STATUS_SENDING,
                            WhatsAppMessage::STATUS_SENT,
                        ])
                            ->whereNull('delivered_at')
                            ->whereNull('read_at')
                            ->where('created_at', '<=', $timeoutCutoff);
                    });
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $sent = 0;
        foreach ($messages as $message) {
            if ($this->process($message)) {
                ++$sent;
            }
        }

        return ['sent' => $sent, 'skipped' => $messages->count() - $sent];
    }

    /**
     * Claiming the message first makes a fallback exactly-once even when the
     * scheduler overlaps with a manual run.
     */
    private function process(WhatsAppMessage $message): bool {
        $reason = $message->status === WhatsAppMessage::STATUS_FAILED
            ? WhatsAppMessage::FALLBACK_REASON_DELIVERY_FAILED
            : WhatsAppMessage::FALLBACK_REASON_DELIVERY_TIMEOUT;

        $claimed = $this->messages->newQuery()
            ->whereKey($message->getKey())
            ->whereNull('fallback_at')
            ->update(['fallback_at' => now(), 'fallback_reason' => $reason]);

        if ($claimed === 0) {
            return false;
        }

        $message->refresh();
        $source = $this->resolveSource($message);

        if (!$source instanceof Transaction && !$source instanceof ApplianceRate) {
            $message->update(['fallback_reason' => WhatsAppMessage::FALLBACK_REASON_SOURCE_MISSING]);

            return false;
        }

        $existing = $this->existingSms($source);
        if ($existing instanceof Sms) {
            $message->update([
                'fallback_sms_id' => $existing->getKey(),
                'fallback_reason' => WhatsAppMessage::FALLBACK_REASON_EXISTING_SMS,
            ]);

            return false;
        }

        try {
            $sms = $this->smsService->sendSms($source, $this->smsTypeFor($message, $source), SmsConfigs::class);
        } catch (\Throwable $exception) {
            Log::error('WhatsApp SMS fallback failed.', [
                'whatsapp_message_id' => $message->getKey(),
                'notification_type' => $message->notification_type,
                'message' => $exception->getMessage(),
            ]);
            $message->update([
                'fallback_at' => null,
                'fallback_reason' => null,
                'fallback_attempts' => $message->fallback_attempts + 1,
            ]);

            return false;
        }

        $message->update(['fallback_sms_id' => $sms?->getKey()]);

        return true;
    }

    private function resolveSource(WhatsAppMessage $message): Transaction|ApplianceRate|null {
        return match ($message->notification_type) {
            WhatsAppMessage::TYPE_PAYMENT_RECEIPT,
            WhatsAppMessage::TYPE_TOKEN_DELIVERY => $this->resolveTransaction($message),
            WhatsAppMessage::TYPE_LOW_BALANCE_WARNING => $this->resolveApplianceRate($message),
            default => null,
        };
    }

    private function resolveTransaction(WhatsAppMessage $message): ?Transaction {
        $transactionId = $message->getAttribute('transaction_id');

        if ($transactionId === null && preg_match('/^transaction:(\d+)$/', (string) $message->reference, $matches) === 1) {
            $transactionId = (int) $matches[1];
        }

        return $transactionId === null ? null : Transaction::query()->find($transactionId);
    }

    private function resolveApplianceRate(WhatsAppMessage $message): ?ApplianceRate {
        if (preg_match('/^appliance_rate:(\d+):(?:upcoming|overdue)$/', (string) $message->reference, $matches) !== 1) {
            return null;
        }

        return ApplianceRate::query()->find((int) $matches[1]);
    }

    private function existingSms(Transaction|ApplianceRate $source): ?Sms {
        if ($source instanceof Transaction) {
            return $source->sms()->latest('id')->first();
        }

        return Sms::query()
            ->where('trigger_type', $source->getMorphClass())
            ->where('trigger_id', $source->getKey())
            ->latest('id')
            ->first();
    }

    private function smsTypeFor(WhatsAppMessage $message, Transaction|ApplianceRate $source): int {
        if ($source instanceof Transaction) {
            return SmsTypes::TRANSACTION_CONFIRMATION;
        }

        return str_ends_with((string) $message->reference, ':overdue')
            ? SmsTypes::OVER_DUE_APPLIANCE_RATE
            : SmsTypes::APPLIANCE_RATE;
    }
}
