<?php

namespace App\Exceptions;

/**
 * Thrown when an agent transaction's down payment exceeds the transaction
 * amount it is meant to cover.
 */
class DownPaymentBiggerThanAmountException extends MpmException {}
