<?php

namespace App\Exceptions;

/**
 * Thrown when a payment is initiated with a provider the tenant has not enabled. The request is
 * well-formed — the provider exists and can initiate payments — so this is a tenant configuration
 * error rather than a validation failure.
 */
class PaymentProviderNotEnabledException extends MpmException {}
