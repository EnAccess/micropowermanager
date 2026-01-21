<?php

namespace App\Plugins\KelinMeter\Providers;

use App\Models\Person\Person;
use App\Plugins\KelinMeter\Observers\PersonObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class ObserverServiceProvider extends ServiceProvider {
    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
        Person::observe(PersonObserver::class);
    }
}
