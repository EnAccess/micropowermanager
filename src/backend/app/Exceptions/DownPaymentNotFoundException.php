<?php

namespace App\Exceptions;

/**
 * Thrown while checking an agent's balance when the transaction carries no
 * down payment to validate against.
 */
class DownPaymentNotFoundException extends MpmException {}
