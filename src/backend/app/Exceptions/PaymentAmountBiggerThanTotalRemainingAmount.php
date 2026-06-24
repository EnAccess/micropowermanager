<?php

namespace App\Exceptions;

/**
 * Thrown when a payment amount exceeds the total remaining amount due.
 */
class PaymentAmountBiggerThanTotalRemainingAmount extends MpmException {}
