<?php

namespace Inensus\Prospect\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    /**
     * @var array<class-string>
     */
    protected $subscribe = [
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
    }
}
