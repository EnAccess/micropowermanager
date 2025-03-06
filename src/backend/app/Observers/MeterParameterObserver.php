<?php

namespace App\Observers;

use App\Models\Meter\MeterParameter;
use App\Services\MeterParameterService;

class MeterParameterObserver {
    public function __construct(private MeterParameterService $meterParameterService) {}

    /**
     * Handle "deleted" event.
     *
     * @param MeterParameter $meterParameter
     *
     * @return void
     */
    public function deleted(MeterParameter $meterParameter): void {
        // set the meter free
        $meter = $meterParameter->meter()->first();
        $meter->in_use = 0;
        $meter->save();
    }

    public function created(MeterParameter $meterParameter): void {
        $meter = $meterParameter->meter()->first();
        $meter->in_use = 1;
        $meter->save();
    }

    public function updated(MeterParameter $meterParameter): void {}
}
