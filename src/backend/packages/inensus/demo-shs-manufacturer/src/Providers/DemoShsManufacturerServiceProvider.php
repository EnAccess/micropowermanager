<?php
namespace Inensus\DemoShsManufacturer\Providers;
use Illuminate\Support\ServiceProvider;
use Inensus\DemoShsManufacturer\Providers\EventServiceProvider;
use Inensus\DemoShsManufacturer\Providers\RouteServiceProvider;
use Inensus\DemoShsManufacturer\Providers\ObserverServiceProvider;
use Inensus\DemoShsManufacturer\Console\Commands\InstallPackage;

class DemoShsManufacturerServiceProvider extends ServiceProvider
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
        $this->mergeConfigFrom(__DIR__ . '/../../config/demo-shs-manufacturer-integration.php', 'demo-shs-manufacturer');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);

    }

    public function publishConfigFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/demo-shs-manufacturer-integration.php' => config_path('demo-shs-manufacturer-integration.php'),
        ]);
    }

    public function publishVueFiles()
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => resource_path('assets/js/plugins/demo-shs-manufacturer'
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