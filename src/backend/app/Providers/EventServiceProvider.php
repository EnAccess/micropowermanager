<?php

namespace App\Providers;

use App\Events\ClusterEvent;
use App\Listeners\AccessRateListener;
use App\Listeners\ClusterGeoListener;
use App\Listeners\LogListener;
use App\Listeners\PaymentEnergyListener;
use App\Listeners\PaymentFailedListener;
use App\Listeners\PaymentLoanListener;
use App\Listeners\PaymentSuccessListener;
use App\Listeners\SmsListener;
use App\Listeners\TransactionFailedListener;
use App\Listeners\TransactionSavedListener;
use App\Listeners\TransactionSuccessListener;
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
        ClusterEvent::class => [ClusterGeoListener::class],
        // string-based Listeners
        'accessRatePayment.initialize' => [AccessRateListener::class],
        'new.log' => [LogListener::class],
        'payment.energy' => [PaymentEnergyListener::class],
        'payment.failed' => [PaymentFailedListener::class],
        'payment.loan' => [PaymentLoanListener::class],
        'payment.successful' => [PaymentSuccessListener::class],
        'sms.stored' => [SmsListener::class],
        'transaction.failed' => [TransactionFailedListener::class],
        'transaction.saved' => [TransactionSavedListener::class],
        'transaction.successful' => [TransactionSuccessListener::class],
        // MPM\User namespace
        UserCreatedEvent::class => [UserListener::class],
    ];

    protected $subscribe = [];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot() {
        parent::boot();
    }
}
