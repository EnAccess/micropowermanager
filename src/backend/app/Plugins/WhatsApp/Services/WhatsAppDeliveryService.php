<?php

namespace App\Plugins\WhatsApp\Services;

use App\Plugins\WhatsApp\Models\WhatsAppMessage;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

class WhatsAppDeliveryService {
    private const array STATUSES = ['sent', 'delivered', 'read', 'failed'];
    private const array BACKOFF = [60, 120, 300, 900, 3600, 10800, 21600];

    public function __construct(private WhatsAppTenantConnectionService $connections, private WhatsAppMessage $messages) {}

    /** @param array<int, array<string, mixed>> $statuses */
    public function accept(int $companyId, array $statuses): void {
        $events = array_map($this->normalize(...), $statuses);
        $this->connections->run($companyId, function (Connection $connection) use ($events, $companyId): void {
            $connection->transaction(function () use ($connection, $events): void {
                foreach ($events as $event) {
                    // Duplicate receipts must not reset retry budgets or processing state.
                    $connection->table('whatsapp_delivery_events')->upsert([$event], ['event_key'], ['event_key']);
                }
            }, 5);
            $keys = array_values(array_unique(array_column($events, 'event_key')));
            if ($this->processKeys($connection, $keys, $companyId)) {
                throw new HttpException(503, 'Webhook processing temporarily unavailable.');
            }
        });
    }

    /** @return array{pending: int, exhausted: int, unsupported: int, processing_errors: bool} */
    public function reconcile(int $companyId): array {
        return $this->connections->run($companyId, function (Connection $connection) use ($companyId): array {
            $keys = $connection->table('whatsapp_delivery_events')->where('state', 'pending')
                ->where('next_attempt_at', '<=', $this->localTime())->orderBy('id')->limit(100)->pluck('event_key')->all();
            $processingErrors = $this->processKeys($connection, $keys, $companyId);

            return [
                'processing_errors' => $processingErrors,
                'pending' => $connection->table('whatsapp_delivery_events')->where('state', 'pending')->count(),
                'exhausted' => $connection->table('whatsapp_delivery_events')->where('state', 'exhausted')->count(),
                'unsupported' => $connection->table('whatsapp_delivery_events')->where('state', 'unsupported')->count(),
            ];
        });
    }

    /** @param array<string, mixed> $status
     * @return array<string, mixed>
     */
    public function normalize(array $status): array {
        if (!is_string($status['id'] ?? null) || $status['id'] === '' || strlen($status['id']) > 255
            || !is_string($status['status'] ?? null) || !preg_match('/^[a-z_-]{1,32}$/D', $status['status'])
            || !is_string($status['timestamp'] ?? null) || !preg_match('/^[0-9]{1,10}$/D', $status['timestamp'])
            || (int) $status['timestamp'] < 1 || (int) $status['timestamp'] > 2147483647) {
            throw new HttpException(400, 'Invalid webhook payload.');
        }
        $code = null;
        if (array_key_exists('errors', $status)) {
            if (!is_array($status['errors']) || !array_is_list($status['errors']) || $status['errors'] === []) {
                throw new HttpException(400, 'Invalid webhook payload.');
            }
            foreach ($status['errors'] as $error) {
                if (!is_array($error) || (!is_int($error['code'] ?? null) && !is_string($error['code'] ?? null))
                    || !preg_match('/^[0-9]{1,20}$/D', (string) $error['code'])) {
                    throw new HttpException(400, 'Invalid webhook payload.');
                }
            }
            $code = (string) $status['errors'][0]['code'];
        }
        $providerAt = gmdate('Y-m-d H:i:s', (int) $status['timestamp']);
        $known = in_array($status['status'], self::STATUSES, true);

        return [
            'event_key' => hash('sha256', json_encode([$status['id'], $status['status'], $providerAt, $code], JSON_THROW_ON_ERROR)),
            'message_id' => $status['id'], 'status' => $status['status'], 'provider_at' => $providerAt,
            'error_code' => $code,
            // Provider free text can contain recipient data or echoed credentials.
            'error_message' => $status['status'] === 'failed' ? 'WhatsApp reported a message delivery failure.' : null,
            'state' => $known ? 'pending' : 'unsupported', 'received_at' => $this->localTime(),
            'next_attempt_at' => $known ? $this->localTime() : null,
            'last_error' => $known ? null : 'unsupported_provider_status',
        ];
    }

    /** @param array<int, string> $keys */
    private function processKeys(Connection $connection, array $keys, int $companyId): bool {
        $failed = false;
        foreach ($keys as $key) {
            try {
                $this->processOne($connection, $key, $companyId);
            } catch (\Throwable) {
                $failed = true;
                // The original transaction rolled back; retain a bounded durable retry.
                $connection->transaction(function () use ($connection, $key, $companyId): void {
                    $event = $connection->table('whatsapp_delivery_events')->where('event_key', $key)->lockForUpdate()->first();
                    if ($event !== null && $event->state === 'pending') {
                        $this->defer($connection, $event, 'processing_failed', $companyId);
                    }
                }, 5);
            }
        }

        return $failed;
    }

    private function processOne(Connection $connection, string $key, int $companyId): void {
        $connection->transaction(function () use ($connection, $key, $companyId): void {
            $event = $connection->table('whatsapp_delivery_events')->where('event_key', $key)->lockForUpdate()->first();
            if ($event === null || $event->state !== 'pending' || $event->next_attempt_at > $this->localTime()) {
                return;
            }
            if ($event->received_at <= now()->utc()->subDay()->format('Y-m-d H:i:s') || $event->attempts >= 8) {
                $this->exhaust($connection, $event, 'reconciliation_expired', $companyId);

                return;
            }
            $query = $connection->table($this->messages->getTable())->where('message_id', $event->message_id);
            if ($connection->getDriverName() === 'mysql') {
                $query->whereRaw('BINARY message_id = ?', [$event->message_id]);
            }
            $matches = $query->orderBy('id')->limit(2)->lockForUpdate()->get();
            if ($matches->isEmpty()) {
                $this->defer($connection, $event, 'message_not_found', $companyId);

                return;
            }
            if ($matches->count() !== 1) {
                $this->exhaust($connection, $event, 'ambiguous_message_id', $companyId);

                return;
            }
            $message = $matches->first();
            if (!in_array($message->status, ['queued', 'sending', ...self::STATUSES], true)) {
                $this->exhaust($connection, $event, 'unsupported_message_status', $companyId);

                return;
            }
            $updates = $this->messageUpdates($message, $event);
            if ($updates !== []) {
                $connection->table($this->messages->getTable())->where('id', $message->id)->update($updates + [
                    'delivery_status_updated_at' => $this->localTime(), 'updated_at' => $this->localTime(),
                ]);
            }
            $connection->table('whatsapp_delivery_events')->where('id', $event->id)->update([
                'state' => 'processed', 'attempts' => $event->attempts + 1, 'next_attempt_at' => null,
                'processed_at' => $this->localTime(), 'notification_type' => $message->notification_type, 'last_error' => null,
            ]);
        }, 5);
    }

    /** @return array<string, mixed> */
    private function messageUpdates(\stdClass $message, \stdClass $event): array {
        $updates = [];
        $dateField = match ($event->status) {
            'sent' => 'provider_sent_at', 'delivered' => 'delivered_at', 'read' => 'read_at', default => null,
        };
        if ($dateField !== null && ($message->{$dateField} === null || $event->provider_at < $message->{$dateField})) {
            $updates[$dateField] = $event->provider_at;
        }
        $current = $message->read_at !== null ? 'read' : ($message->delivered_at !== null ? 'delivered' : $message->status);
        $currentTime = $message->provider_status_at;
        $accept = match ($event->status) {
            'read' => $current !== 'read' || $currentTime === null || $event->provider_at > $currentTime,
            'delivered' => $current !== 'read' && ($current !== 'delivered' || $currentTime === null || $event->provider_at > $currentTime),
            'sent' => in_array($current, ['queued', 'sending'], true)
                || (in_array($current, ['sent', 'failed'], true) && ($currentTime === null || $event->provider_at > $currentTime)),
            'failed' => !in_array($current, ['delivered', 'read'], true)
                && ($currentTime === null || $event->provider_at > $currentTime
                    || ($event->provider_at === $currentTime && ($current !== 'failed' || strcmp($event->error_code ?? '', $message->provider_error_code ?? '') > 0))),
            default => false,
        };
        if ($accept) {
            $updates += [
                'status' => $event->status, 'provider_status_at' => $event->provider_at,
                'failed_at' => $event->status === 'failed' ? $event->provider_at : null,
                'provider_error_code' => $event->status === 'failed' ? $event->error_code : null,
                'error_message' => $event->status === 'failed' ? $event->error_message : null,
            ];
        }

        return $updates;
    }

    private function defer(Connection $connection, \stdClass $event, string $reason, int $companyId): void {
        $attempts = $event->attempts + 1;
        if ($attempts >= 8 || $event->received_at <= now()->utc()->subDay()->format('Y-m-d H:i:s')) {
            $this->exhaust($connection, $event, $reason, $companyId);

            return;
        }
        $connection->table('whatsapp_delivery_events')->where('id', $event->id)->update([
            'attempts' => $attempts, 'last_error' => $reason,
            'next_attempt_at' => now()->utc()->addSeconds(self::BACKOFF[$attempts - 1])->format('Y-m-d H:i:s'),
        ]);
    }

    private function exhaust(Connection $connection, \stdClass $event, string $reason, int $companyId): void {
        $connection->table('whatsapp_delivery_events')->where('id', $event->id)->update([
            'state' => 'exhausted', 'attempts' => min(8, $event->attempts + 1), 'last_error' => $reason, 'next_attempt_at' => null,
        ]);
        Log::warning('WhatsApp delivery reconciliation requires attention.', ['companyId' => $companyId, 'reason' => $reason]);
    }

    private function localTime(): string {
        return now()->utc()->format('Y-m-d H:i:s');
    }
}
