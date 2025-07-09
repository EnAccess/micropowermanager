<?php

namespace App\Events;

use App\Models\Meter\Meter;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AccessRatePaymentInitialize.
 *
 * Dispatch this event to initialize the payment of the first access rate.
 * If the Meter's tariff has an access rate configured.
 *
 * Note: This is specific to Meters.
 *
 * @property Meter $meter The Meter for which the access rate payment should be initialized.
 */
class AccessRatePaymentInitialize {
    use Dispatchable;
    use SerializesModels;

    public function __construct(public Meter $meter) {}
}
