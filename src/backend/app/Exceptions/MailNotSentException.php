<?php

namespace App\Exceptions;

/**
 * Thrown when an email could not be sent by the mail transport.
 */
class MailNotSentException extends MpmException {
    protected int $httpStatusCode = 500;
}
