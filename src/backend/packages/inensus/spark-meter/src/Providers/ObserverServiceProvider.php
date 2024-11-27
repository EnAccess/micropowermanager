<?php

namespace Inensus\SparkMeter\Providers;

use App\Models\GeographicalInformation;
use App\Models\Meter\MeterTariff;
use App\Models\Person\Person;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\SparkMeter\Observers\GeographicalInformationObserver;
use Inensus\SparkMeter\Observers\MeterTariffObserver;
use Inensus\SparkMeter\Observers\PersonObserver;

class ObserverServiceProvider extends ServiceProvider {
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot() {
        parent::boot();
        Person::observe(PersonObserver::class);
        GeographicalInformation::observe(GeographicalInformationObserver::class);
        MeterTariff::observe(MeterTariffObserver::class);
    }
}
