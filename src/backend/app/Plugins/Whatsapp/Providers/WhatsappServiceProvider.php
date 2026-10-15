<?php

namespace App\Plugins\Whatsapp\Providers;

use Illuminate\Support\ServiceProvider;
use App\Plugins\Whatsapp\Console\Commands\InstallPackage;
use App\Plugins\Whatsapp\Providers\EventServiceProvider;
use App\Plugins\Whatsapp\Providers\RouteServiceProvider;
use App\Plugins\Whatsapp\Providers\ObserverServiceProvider;

class WhatsappServiceProvider extends ServiceProvider
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
