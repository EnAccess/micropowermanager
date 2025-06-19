<?php

namespace App\Providers;

use App\Events\ClusterEvent;
use App\Listeners\AccessRateSubscriber;
use App\Listeners\ClusterGeoListener;
use App\Listeners\HistorySubscriber;
use App\Listeners\LogSubscriber;
use App\Listeners\PaymentSubscriber;
use App\Listeners\SmsSubscriber;
use App\Listeners\TransactionSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use MPM\User\Events\UserCreatedEvent;
use MPM\User\UserListener;

class EventServiceProvider extends ServiceProvider {
    /**
     * The event listener mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ClusterEvent::class => [ClusterGeoListener::class],
        UserCreatedEvent::class => [UserListener::class],
        'accessRatePayment.initialize' => [AccessRateListener::class],
    ];

    protected $subscribe = [
        PaymentSubscriber::class,
        TransactionSubscriber::class,
        HistorySubscriber::class,
        LogSubscriber::class,
        SmsSubscriber::class,
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
