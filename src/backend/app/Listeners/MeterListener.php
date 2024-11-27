<?php

namespace App\Listeners;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class MeterListener {
    /**
     * Sets the in_use to true.
     *
     * @param int $meter_id
     *
     * @return void
     */
    public function onParameterSaved(int $meter_id): void {
        Log::debug('listener Core', []);
    }

    public function subscribe(Dispatcher $events): void {
        $events->listen('meterparameter.saved', 'App\Listeners\MeterListener@onParameterSaved');
    }
}
