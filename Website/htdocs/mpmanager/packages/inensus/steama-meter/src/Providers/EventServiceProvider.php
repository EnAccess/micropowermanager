<?php

namespace Inensus\SteamaMeter\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\SteamaMeter\Listeners\MeterParameterListener;
use Inensus\SteamaMeter\Listeners\SmsListener;

class EventServiceProvider extends ServiceProvider
{
    protected $subscribe = [
        //  MeterParameterListener::class,
        SmsListener::class
    ];
    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
