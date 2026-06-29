<?php

namespace App\Plugins\MesombPaymentProvider\Exceptions;

use App\Exceptions\MpmException;

/**
 * Thrown when a MeSomb payer is linked to more than one connected meter, so the
 * payment cannot be attributed to a single meter.
 */
class MesombPayerMustHaveOnlyOneConnectedMeterException extends MpmException {}
