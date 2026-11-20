<?php

declare(strict_types=1);

namespace App\Enums;

// The docblocks below are public and get rendered into the API docs.

/**
 * A token a token issuer can vend for a device, and the `type` of an ad-hoc token request.
 * Every token issuer declares its own set, because the calls on
 * {@see \App\Lib\ITokenIssuer} that it cannot serve throw instead of working
 * and there is no safe way to discover that by trying them.
 */
enum VendingCapability: string {
    /** Adds credit to the device: days for solar home systems and e-bikes, kWh for meters. */
    case Credit = 'credit';
    /** Releases a solar home system from pay-as-you-go for good. */
    case Unlock = 'unlock';
    /** Resets a solar home system's state, taking its remaining days to zero. */
    case Reset = 'reset';
    /** Sets a meter's remaining credit to zero, leaving the customer without energy. */
    case ClearCredit = 'clear_credit';
}
