<?php

namespace App\Events;

use App\Models\Sms;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched by SMS gateway callback controllers when an incoming SMS is received.
 *
 * Listeners react to inbound messages (e.g. transaction parsing, resend-information
 * replies, gateway-specific routing). The event carries the raw sender and message
 * so listeners can act even when the sender is not a known MPM customer.
 *
 * @property string   $sender  Raw sender phone number as reported by the gateway (not normalized).
 * @property string   $message Body of the incoming SMS.
 * @property Sms|null $sms     Persisted SMS record when the sender matches an MPM customer address; null otherwise.
 */
class SmsStoredEvent {
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $sender,
        public string $message,
        public ?Sms $sms = null,
    ) {}
}
