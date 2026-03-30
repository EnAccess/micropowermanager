<?php

namespace App\Plugins\SparkMeter\Providers;

use App\Events\SmsStoredEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Plugins\SparkMeter\Listeners\SmsListener;
use App\Plugins\SparkMeter\Listeners\TransactionListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {
    protected $listen = [
        SmsStoredEvent::class => [SmsListener::class],
        TransactionSuccessfulEvent::class => [TransactionListener::class],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void {
        parent::boot();
    }
}
