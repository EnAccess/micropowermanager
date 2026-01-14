<?php

namespace Inensus\{{Plugin-Name}}\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\{{Plugin-Name}}\Console\Commands\InstallPackage;
use Inensus\{{Plugin-Name}}\Providers\EventServiceProvider;
use Inensus\{{Plugin-Name}}\Providers\RouteServiceProvider;
use Inensus\{{Plugin-Name}}\Providers\ObserverServiceProvider;

class {{Plugin-Name}}ServiceProvider extends ServiceProvider
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
