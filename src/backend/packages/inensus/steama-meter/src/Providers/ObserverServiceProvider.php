<?php

namespace Inensus\SteamaMeter\Providers;

use App\Models\GeographicalInformation;
use App\Models\Person\Person;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\SteamaMeter\Observers\GeographicalInformationObserver;
use Inensus\SteamaMeter\Observers\PersonObserver;

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
    }
}
