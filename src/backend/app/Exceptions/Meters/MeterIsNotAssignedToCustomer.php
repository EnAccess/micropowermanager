<?php

namespace App\Exceptions\Meters;

use App\Exceptions\MpmException;

/**
 * Thrown when an operation requires a meter to be assigned to a customer but it is not.
 */
class MeterIsNotAssignedToCustomer extends MpmException {}
