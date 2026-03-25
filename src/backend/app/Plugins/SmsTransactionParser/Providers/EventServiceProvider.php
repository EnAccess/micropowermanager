<?php

namespace App\Plugins\SmsTransactionParser\Providers;

use App\Events\SmsStoredEvent;
use App\Plugins\SmsTransactionParser\Listeners\SmsStoredListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    /** @var array<class-string, array<int, class-string>> */
    protected $listen = [
        SmsStoredEvent::class => [
            SmsStoredListener::class,
        ],
    ];

    /**
     * @var array<class-string>
     */
    protected $subscribe = [];

    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
    }
}
