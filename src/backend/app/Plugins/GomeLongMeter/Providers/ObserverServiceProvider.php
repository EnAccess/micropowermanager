<?php

namespace App\Plugins\GomeLongMeter\Providers;

use App\Models\Meter\MeterTariff;
use App\Plugins\GomeLongMeter\Observers\MeterTariffObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class ObserverServiceProvider extends ServiceProvider {
    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
        // removed observing of tariffs since GomeLong's API does not support it
        // MeterTariff::observe(MeterTariffObserver::class);
    }
}
