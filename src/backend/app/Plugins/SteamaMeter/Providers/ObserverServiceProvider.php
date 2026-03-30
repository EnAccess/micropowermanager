<?php

namespace App\Plugins\SteamaMeter\Providers;

use App\Models\GeographicalInformation;
use App\Models\Person\Person;
use App\Plugins\SteamaMeter\Observers\GeographicalInformationObserver;
use App\Plugins\SteamaMeter\Observers\PersonObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class ObserverServiceProvider extends ServiceProvider {
    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
        Person::observe(PersonObserver::class);
        GeographicalInformation::observe(GeographicalInformationObserver::class);
    }
}
