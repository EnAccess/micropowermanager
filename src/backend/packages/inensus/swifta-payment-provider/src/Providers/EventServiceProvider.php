<?php

namespace Inensus\SwiftaPaymentProvider\Providers;

use App\Events\TransactionFailedEvent;
use App\Events\TransactionSuccessfulEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\SwiftaPaymentProvider\Listeners\TransactionFailedListener;
use Inensus\SwiftaPaymentProvider\Listeners\TransactionSuccessfulListener;

class EventServiceProvider extends ServiceProvider {
    // commended out because it is not used for cloud
    protected $listen = [
        // TransactionFailedEvent::class => [TransactionFailedListener::class],
        // TransactionSuccessfulEvent::class => [TransactionSuccessfulListener::class],
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
