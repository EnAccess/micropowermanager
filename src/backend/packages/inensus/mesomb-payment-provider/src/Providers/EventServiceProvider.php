<?php

namespace Inensus\MesombPaymentProvider\Providers;

use App\Events\TransactionFailedEvent;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\MesombPaymentProvider\Listeners\TransactionFailedListener;
use Inensus\MesombPaymentProvider\Listeners\TransactionSuccessfulListener;

class EventServiceProvider extends ServiceProvider {
    protected $subscribe = [
        // TransactionFailedEvent::class => [TransactionFailedListener::class],
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
