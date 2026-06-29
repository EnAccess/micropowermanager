<?php

namespace App\Exceptions;

/**
 * Thrown when an operation is attempted on a transaction that has not been initialized.
 */
class TransactionNotInitializedException extends MpmException {}
