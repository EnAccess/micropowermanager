<?php

namespace App\Exceptions;

/**
 * Thrown while checking an agent's balance when the transaction has no amount
 * to validate against.
 */
class TransactionAmountNotFoundException extends MpmException {}
