<?php

namespace Inensus\SparkMeter\Providers;

use App\Events\SmsStoredEvent;
use App\Events\TransactionSuccessfulEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\SparkMeter\Listeners\SmsListener;
use Inensus\SparkMeter\Listeners\TransactionListener;

class EventServiceProvider extends ServiceProvider {
    protected $listen = [
        SmsStoredEvent::class => [SmsListener::class],
        TransactionSuccessfulEvent::class => [TransactionListener::class],
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
