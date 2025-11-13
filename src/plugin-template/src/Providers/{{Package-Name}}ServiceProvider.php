<?php
use Illuminate\Support\ServiceProvider;
use Inensus\{{Package-Name}}\Providers\EventServiceProvider;
use Inensus\{{Package-Name}}\Providers\RouteServiceProvider;
use Inensus\{{Package-Name}}\Providers\ObserverServiceProvider;

class {{Package-Name}}ServiceProvider extends ServiceProvider
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
