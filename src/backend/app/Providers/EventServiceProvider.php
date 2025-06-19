<?php

namespace App\Providers;

use App\Events\ClusterEvent;
use App\Listeners\AccessRateListener;
use App\Listeners\ClusterGeoListener;
use App\Listeners\HistorySubscriber;
use App\Listeners\LogListener;
use App\Listeners\PaymentEnergyListener;
use App\Listeners\PaymentFailedListener;
use App\Listeners\PaymentLoanListener;
use App\Listeners\PaymentSuccessListener;
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
        // default namespace
        'accessRatePayment.initialize' => [AccessRateListener::class],
        ClusterEvent::class => [ClusterGeoListener::class],
        'new.log' => [LogListener::class],
        'payment.successful' => [PaymentSuccessListener::class],
        'payment.failed' => [PaymentFailedListener::class],
        'payment.energy' => [PaymentEnergyListener::class],
        'payment.loan' => [PaymentLoanListener::class],
        // MPM\User namespace
        UserCreatedEvent::class => [UserListener::class],
    ];

    protected $subscribe = [
        TransactionSubscriber::class,
        HistorySubscriber::class,
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
