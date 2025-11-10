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
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations();
            $this->commands([InstallPackage::class]);
        }
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/{{package-name}}-integration.php', '{{package-name}}');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);

    }

    public function publishConfigFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/{{package-name}}-integration.php' => config_path('{{package-name}}-integration.php'),
        ]);
    }

    public function publishVueFiles()
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => resource_path('assets/js/plugins/{{package-name}}'
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