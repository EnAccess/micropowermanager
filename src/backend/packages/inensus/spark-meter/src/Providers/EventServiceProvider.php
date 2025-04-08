<?php

namespace Inensus\SparkMeter\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\SparkMeter\Listeners\SmsListener;

class EventServiceProvider extends ServiceProvider {
    protected $subscribe = [
        SmsListener::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot() {
        parent::boot();
    }
}
