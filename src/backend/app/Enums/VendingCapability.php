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
    /** Adds credit to the device: For example days for Energy-as-a-service or PAYGO device like solar home systems and e-bikes, top up kWh for meters. */
    case AddCredit = 'add_credit';
    /** Unlocks a PAYGO device from pay-as-you-go mode, allowing free operation. */
    case UnlockPaygo = 'unlock_paygo';
    /** Resets Energy-as-a-service or PAYGO device's credit to zero, essentially blocking it operation. Can be useful when devices get redistributed to other customers. */
    case ResetCredit = 'reset_credit';
    /** Sets a meter's remaining credit to zero, leaving the customer without energy. */
    case ClearCredit = 'clear_credit';
}
