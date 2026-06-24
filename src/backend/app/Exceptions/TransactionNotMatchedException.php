<?php

namespace App\Exceptions;

/**
 * Thrown when an incoming transaction cannot be matched to an existing record.
 */
class TransactionNotMatchedException extends MpmException {}
