<?php

namespace App\Plugins\FlutterwavePaymentProvider\Providers;

use Illuminate\Support\ServiceProvider;
use App\Plugins\FlutterwavePaymentProvider\Console\Commands\InstallPackage;
use App\Plugins\FlutterwavePaymentProvider\Providers\EventServiceProvider;
use App\Plugins\FlutterwavePaymentProvider\Providers\RouteServiceProvider;
use App\Plugins\FlutterwavePaymentProvider\Providers\ObserverServiceProvider;

class FlutterwavePaymentProviderServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->register(RouteServiceProvider::class);
        $this->commands([InstallPackage::class]);
    }

    public function register()
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);

    }
}
