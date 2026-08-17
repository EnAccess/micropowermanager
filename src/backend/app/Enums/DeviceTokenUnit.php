<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Token;

// The docblocks below are public and get rendered into the API docs.

/**
 * The unit an ad-hoc token request is denominated in.
 * `currency` is handed to the manufacturer as-is; the credit units are converted
 * to a currency amount first, because manufacturer APIs only accept money.
 */
enum DeviceTokenUnit: string {
    /** A currency amount, in the company's currency. */
    case Currency = 'currency';
    /** Days of usage, for solar home systems and e-bikes. */
    case Days = Token::UNIT_DAYS;
    /** Kilowatt hours, for meters. */
    case Kwh = Token::UNIT_KWH;
}
