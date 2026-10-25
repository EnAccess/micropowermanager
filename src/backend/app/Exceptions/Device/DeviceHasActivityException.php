<?php

namespace App\Exceptions\Device;

use App\Exceptions\MpmException;

/**
 * Thrown when changing a device's identity would detach its history. Transactions,
 * tokens and appliance sales reference a device by its serial string, so serial
 * changes and deletions are only safe before any such activity exists.
 */
class DeviceHasActivityException extends MpmException {}
