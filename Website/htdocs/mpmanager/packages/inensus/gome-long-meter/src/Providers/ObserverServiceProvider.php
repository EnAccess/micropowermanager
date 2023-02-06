<?php

namespace Inensus\GomeLongMeter\Providers;

use App\Models\Meter\MeterTariff;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\GomeLongMeter\Observers\MeterTariffObserver;

class ObserverServiceProvider extends ServiceProvider
{
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        MeterTariff::observe(MeterTariffObserver::class);
    }
}