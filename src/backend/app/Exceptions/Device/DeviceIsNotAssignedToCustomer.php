<?php

namespace App\Exceptions\Device;

use App\Exceptions\MpmException;

/**
 * Thrown when an operation requires a device to be assigned to a customer but it is not.
 */
class DeviceIsNotAssignedToCustomer extends MpmException {}
