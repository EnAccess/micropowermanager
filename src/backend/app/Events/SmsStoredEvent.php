<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * SmsStoredEvent.
 *
 * Dispatch this event to asynchronously store an SMS.
 * A corresponding listener will send the event.
 *
 * @property string $sender  The sender of the SMS.
 * @property string $message The message content of the SMS.
 */
class SmsStoredEvent {
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $sender,
        public string $message,
    ) {}
}
