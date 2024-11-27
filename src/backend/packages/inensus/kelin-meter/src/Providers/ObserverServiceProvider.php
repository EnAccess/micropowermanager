<?php

namespace Inensus\KelinMeter\Providers;

use App\Models\Person\Person;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\KelinMeter\Observers\PersonObserver;

class ObserverServiceProvider extends ServiceProvider {
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot() {
        parent::boot();
        Person::observe(PersonObserver::class);
    }
}
