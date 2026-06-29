<?php

namespace App\Exceptions;

/**
 * Thrown when registering a company whose owner email is already in use by
 * another account.
 */
class OwnerEmailAlreadyExistsException extends MpmException {}
