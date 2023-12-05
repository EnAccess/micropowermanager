<?php

namespace App\Listeners;

use App\Exceptions\AccessRates\NoAccessRateFound;
use App\Models\AccessRate\AccessRate;
use App\Models\AccessRate\AccessRatePayment;
use App\Models\Meter\Meter;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class AccessRateListener
{
    public function initializeAccessRatePayment(Meter $meter): void
    {
        try {
            $accessRate = $meter->tariff()->first()->accessRate;
            if (!$accessRate) {
                throw new NoAccessRateFound('Access Rate is not set');
            }
            $nextPaymentDate = Carbon::now()->addDays($accessRate->period)->toDateString();
            $accessRatePayment = new AccessRatePayment();
            $accessRatePayment->accessRate()->associate($accessRate);
            $accessRatePayment->meter()->associate($meter);
            $accessRatePayment->due_date = $nextPaymentDate;
            $accessRatePayment->debt = 0;
        } catch (NoAccessRateFound $exception) {
            Log::error($exception->getMessage(), ['id' => 'fj3g98suiq3z89fdhfjlsa']);
        }
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(
            'accessRatePayment.initialize',
            '\App\Listeners\AccessRateListener@initializeAccessRatePayment'
        );
    }
}
