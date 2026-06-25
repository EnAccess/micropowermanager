<?php

namespace App\Exceptions;

/**
 * Thrown when an incoming payment cannot be matched to a known payment provider.
 */
class PaymentProviderNotIdentified extends MpmException {}
