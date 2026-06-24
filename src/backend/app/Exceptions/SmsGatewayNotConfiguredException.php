<?php

namespace App\Exceptions;

/**
 * Thrown when an SMS is about to be sent but no SMS gateway is configured for
 * the tenant.
 */
class SmsGatewayNotConfiguredException extends MpmException {
    public function __construct(string $message = 'No active SMS provider is configured. Please configure an SMS gateway in Main Settings like AfricasTalking or TextBee.') {
        parent::__construct($message);
    }
}
