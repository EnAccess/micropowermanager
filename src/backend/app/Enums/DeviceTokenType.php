<?php

declare(strict_types=1);

namespace App\Enums;

// The docblocks below are public and get rendered into the API docs.

/**
 * What an out-of-band token request asks the manufacturer to do to a unit.
 * `credit` is priced from the requested amount; the other two carry no credit and
 * are only offered where the manufacturer declares support for them.
 */
enum DeviceTokenType: string {
    /** Adds credit to the unit, read in the requested unit. */
    case Credit = 'credit';
    /** Releases the unit from pay-as-you-go for good. */
    case Unlock = 'unlock';
    /** Sets the unit's remaining credit to zero, so a repossessed unit can be resold. */
    case Reset = 'reset';
}
