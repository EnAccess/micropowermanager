<?php

namespace App\Providers;

use App\Listeners\AccessRateListener;
use App\Listeners\HistoryListener;
use App\Listeners\LogListener;
use App\Listeners\MeterListener;
use App\Listeners\PaymentListener;
use App\Listeners\PaymentPeriodListener;
use App\Listeners\SmsListener;
use App\Listeners\SolarDataListener;
use App\Listeners\TokenListener;
use App\Listeners\TransactionListener;
use App\Listeners\SoldApplianceListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        'App\Events\ClusterEvent' => ['App\Listeners\ClusterGeoListener',],
    ];

    protected $subscribe = [
        AccessRateListener::class,
        MeterListener::class,
        PaymentListener::class,
        TokenListener::class,
        TransactionListener::class,
        HistoryListener::class,
        PaymentPeriodListener::class,
        LogListener::class,
        SolarDataListener::class,
        SmsListener::class,
        SoldApplianceListener::class
    ];


    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
