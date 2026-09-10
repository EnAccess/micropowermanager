<?php

namespace App\Exceptions;

/**
 * Thrown when a payment provider needs a phone number to push the payment request to, but the
 * customer has no primary address phone and the caller supplied no override.
 */
class PayerPhoneNotFoundException extends MpmException {}
