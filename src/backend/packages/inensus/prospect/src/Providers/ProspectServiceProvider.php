<?php

namespace Inensus\Prospect\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\Prospect\Providers\EventServiceProvider;
use Inensus\Prospect\Providers\RouteServiceProvider;
use Inensus\Prospect\Providers\ObserverServiceProvider;

class ProspectServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations();
            $this->commands([InstallPackage::class]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/prospect-integration.php', 'prospect');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);

    }

    public function publishConfigFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/prospect-integration.php' => config_path('prospect-integration.php'),
        ]);
    }

    public function publishVueFiles()
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => resource_path('assets/js/plugins/prospect'
            ),
        ], 'vue-components');
    }

    public function publishMigrations()
    {
        if (!class_exists('CreateSmGrids')) {
            $timestamp = date('Y_m_d_His');
           $this->publishes([

            ], 'migrations');
        }
    }
}
