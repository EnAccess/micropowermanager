<?php

namespace App\Exceptions;

/**
 * Thrown when a payment amount is zero or negative.
 */
class PaymentAmountSmallerThanZero extends MpmException {}
