<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * NewLogEvent.
 *
 * Dispatch this event to asynchronously record an event to `log` table.
 *
 * The `$logData` array must have the following structure:
 *
 * ```
 * [
 *   'user_id'  => (int)   ID of the user performing the action,
 *   'affected' => (Model) The object or entity affected by the action,
 *   'action'   => (string) Description of the action performed,
 * ]
 * ```
 *
 * @property array $logData The data to include in the `log` table.
 */
class NewLogEvent {
    use Dispatchable;
    use SerializesModels;

    public function __construct(public array $logData) {}
}
