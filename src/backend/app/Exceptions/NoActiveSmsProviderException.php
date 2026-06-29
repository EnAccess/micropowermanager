<?php

namespace App\Exceptions;

/**
 * Thrown when no active SMS provider is configured to send a message.
 */
class NoActiveSmsProviderException extends MpmException {}
