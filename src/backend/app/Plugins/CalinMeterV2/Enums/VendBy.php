<?php

namespace App\Plugins\CalinMeterV2\Enums;

/**
 * What the amount of a vend request means to Calin.
 */
enum VendBy: string {
    /** MPM converts the payment to kWh with its tariff and asks Calin for that energy. */
    case Energy = 'energy';

    /** MPM sends the paid amount and Calin converts it to kWh with the tariff stored on its side. */
    case AmountPaid = 'amount_paid';
}
