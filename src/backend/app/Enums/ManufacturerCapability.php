<?php

declare(strict_types=1);

namespace App\Enums;

// The docblocks below are public and get rendered into the API docs.

/**
 * An out-of-band operation a manufacturer's API can carry out on a device.
 * Every manufacturer declares its own set, because the calls on
 * {@see \App\Lib\IManufacturerAPI} that it cannot serve throw instead of working
 * and there is no safe way to discover that by trying them.
 */
enum ManufacturerCapability: string {
    /** Can vend a token that adds credit to a device. */
    case CreditToken = 'credit_token';
    /** Can vend a token that releases a device from pay-as-you-go for good. */
    case UnlockToken = 'unlock_token';
    /** Can vend a token that sets a device's remaining credit to zero. */
    case ResetToken = 'reset_token';
}
