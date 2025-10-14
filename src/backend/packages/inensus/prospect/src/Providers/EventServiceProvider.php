<?php

namespace Inensus\Prospect\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    protected $subscribe = [
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
    }
}
