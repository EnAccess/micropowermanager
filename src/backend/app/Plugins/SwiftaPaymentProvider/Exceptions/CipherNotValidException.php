<?php

namespace App\Plugins\SwiftaPaymentProvider\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when the cipher supplied in a Swifta request cannot be verified or
 * decrypted.
 */
class CipherNotValidException extends MpmException {}
