<?php

namespace App\Exceptions;

/**
 * Thrown when required SMS reference data is missing while composing a message.
 */
class MissingSmsReferencesException extends MpmException {}
