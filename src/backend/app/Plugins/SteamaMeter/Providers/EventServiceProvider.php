<?php

namespace App\Plugins\SteamaMeter\Providers;

use App\Events\SmsStoredEvent;
use App\Plugins\SteamaMeter\Listeners\SmsListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    protected $listen = [
        SmsStoredEvent::class => [SmsListener::class],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
    }
}
