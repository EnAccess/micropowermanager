<?php

namespace App\Events;

use App\Models\Sms;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SmsStoredEvent.
 *
 * Dispatch this event to asynchronously store an SMS.
 * A corresponding listener will send the event.
 *
 * @property string   $sender  The sender of the SMS.
 * @property string   $message The message content of the SMS.
 * @property Sms|null $sms     The SMS model instance, if available.
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
