<?php

namespace App\Exceptions;

/**
 * Thrown when the owner associated with a ticket cannot be found.
 */
class TicketOwnerNotFoundException extends MpmException {
    protected int $httpStatusCode = 404;
}
