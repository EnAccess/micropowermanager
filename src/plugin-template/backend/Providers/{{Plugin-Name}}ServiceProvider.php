<?php

namespace App\Plugins\{{Plugin-Name}}\Providers;

use Illuminate\Support\ServiceProvider;
use App\Plugins\{{Plugin-Name}}\Console\Commands\InstallPackage;
use App\Plugins\{{Plugin-Name}}\Providers\EventServiceProvider;
use App\Plugins\{{Plugin-Name}}\Providers\RouteServiceProvider;
use App\Plugins\{{Plugin-Name}}\Providers\ObserverServiceProvider;

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
