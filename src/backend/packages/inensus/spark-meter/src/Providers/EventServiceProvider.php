<?php

namespace Inensus\SparkMeter\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\SparkMeter\Listeners\SmsListener;
use Inensus\SparkMeter\Listeners\TransactionListener;

class EventServiceProvider extends ServiceProvider {
    protected $listen = [
        'sms.stored' => [SmsListener::class],
        'transaction.successful' => [TransactionListener::class],
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
