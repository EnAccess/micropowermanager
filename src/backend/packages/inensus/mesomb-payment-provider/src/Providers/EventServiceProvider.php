<?php

namespace Inensus\MesombPaymentProvider\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Inensus\MesombPaymentProvider\Listeners\TransactionListener;

class EventServiceProvider extends ServiceProvider {
    protected $subscribe = [
        //   TransactionListener::class
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
