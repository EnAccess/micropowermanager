<?php

namespace App\Listeners;

use App\Events\AccessRatePaymentInitialize;
use App\Exceptions\AccessRates\NoAccessRateFound;
use App\Models\AccessRate\AccessRatePayment;
use App\Models\Meter\Meter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AccessRateListener {
    /**
     * Check if the meter's tariff has an access rate, and if so
     * initiate the first payment of the access rate.
     */
    public function initializeAccessRatePayment(Meter $meter): void {
        try {
            $tariff = $meter->tariff()->first();
            $accessRate = $tariff?->accessRate;
            if (!$accessRate) {
                throw new NoAccessRateFound('Access Rate is not set');
            }
            $nextPaymentDate = Carbon::now()->addDays($accessRate->period);
            $accessRatePayment = new AccessRatePayment();
            $accessRatePayment->accessRate()->associate($accessRate);
            $accessRatePayment->meter()->associate($meter);
            $accessRatePayment->due_date = $nextPaymentDate;
            $accessRatePayment->debt = 0;
            // FIXME: $accessRatePayment is not getting saved, i.e. not persisted
            // into the database. Is this correct?
            // $accessRatePayment->save();
        } catch (NoAccessRateFound $exception) {
            Log::error($exception->getMessage(), ['id' => 'fj3g98suiq3z89fdhfjlsa']);
        }
    }

    public function handle(AccessRatePaymentInitialize $event): void {
        $this->initializeAccessRatePayment($event->meter);
    }
}
