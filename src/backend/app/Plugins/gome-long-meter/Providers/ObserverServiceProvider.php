<?php

namespace Inensus\GomeLongMeter\Providers;

use App\Models\Meter\MeterTariff;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\GomeLongMeter\Observers\MeterTariffObserver;

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
