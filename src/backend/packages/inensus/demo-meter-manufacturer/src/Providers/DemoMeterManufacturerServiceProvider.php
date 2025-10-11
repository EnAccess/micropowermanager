<?php
namespace Inensus\DemoMeterManufacturer\Providers;
use Illuminate\Support\ServiceProvider;
use Inensus\DemoMeterManufacturer\Providers\EventServiceProvider;
use Inensus\DemoMeterManufacturer\Providers\RouteServiceProvider;
use Inensus\DemoMeterManufacturer\Providers\ObserverServiceProvider;
use Inensus\DemoMeterManufacturer\Console\Commands\InstallPackage;

class DemoMeterManufacturerServiceProvider extends ServiceProvider
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
        $this->mergeConfigFrom(__DIR__ . '/../../config/demo-meter-manufacturer.php', 'demo-meter-manufacturer');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);

    }

    public function publishConfigFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/demo-meter-manufacturer.php' => config_path('demo-meter-manufacturer.php'),
        ]);
    }

    public function publishVueFiles()
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => resource_path('assets/js/plugins/demo-meter-manufacturer'
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