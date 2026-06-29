<?php

namespace App\Exceptions;

/**
 * Thrown when a transaction is not in a valid state to process an incoming request.
 */
class TransactionIsInvalidForProcessingIncomingRequestException extends MpmException {}
