<?php

namespace App\Exceptions;

/**
 * Thrown when a transaction amount is insufficient to cover the requested operation.
 */
class TransactionAmountNotEnoughException extends MpmException {}
