<?php

namespace App\Listeners;

use App\Exceptions\AccessRates\NoAccessRateFound;
use App\Models\Meter\MeterParameter;
use App\PaymentHandler\AccessRate;
use Illuminate\Events\Dispatcher;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class AccessRateListener
{
    public function initializeAccessRatePayment(MeterParameter $meterParameter): void
    {
        try {
            $accessRatePayment = AccessRate::withMeterParameters($meterParameter);
            $accessRatePayment->initializeAccessRatePayment()->save();
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
