<?php

namespace Inensus\SwiftaPaymentProvider\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\SwiftaPaymentProvider\Listeners\TransactionFailedListener;
use Inensus\SwiftaPaymentProvider\Listeners\TransactionSuccessfulListener;

class EventServiceProvider extends ServiceProvider {
    // commended out because it is not used for cloud
    protected $listen = [
        // 'transaction.failed' => [TransactionFailedListener::class],
        // 'transaction.successful' => [TransactionSuccessfulListener::class],
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
