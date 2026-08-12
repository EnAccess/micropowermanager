<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

/**
 * Thrown when the cipher supplied in a Swifta request cannot be verified or
 * decrypted.
 */
class CipherNotValidException extends SwiftaException {}
