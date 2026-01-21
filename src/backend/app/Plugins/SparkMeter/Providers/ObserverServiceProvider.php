<?php

namespace App\Plugins\SparkMeter\Providers;

use App\Models\GeographicalInformation;
use App\Models\Meter\MeterTariff;
use App\Models\Person\Person;
use App\Plugins\SparkMeter\Observers\GeographicalInformationObserver;
use App\Plugins\SparkMeter\Observers\MeterTariffObserver;
use App\Plugins\SparkMeter\Observers\PersonObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class ObserverServiceProvider extends ServiceProvider {
    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
        Person::observe(PersonObserver::class);
        GeographicalInformation::observe(GeographicalInformationObserver::class);
        MeterTariff::observe(MeterTariffObserver::class);
    }
}
