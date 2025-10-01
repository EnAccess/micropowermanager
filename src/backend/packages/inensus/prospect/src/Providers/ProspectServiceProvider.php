<?php

namespace Inensus\Prospect\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\Prospect\Providers\EventServiceProvider;
use Inensus\Prospect\Providers\RouteServiceProvider;
use Inensus\Prospect\Providers\ObserverServiceProvider;
use Inensus\Prospect\Console\Commands\InstallPackage;

class ProspectServiceProvider extends ServiceProvider
{
    public function boot(Filesystem $filesystem)
    {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
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

    public function publishMigrations(Filesystem $filesystem): void {
        $this->publishes([
            __DIR__ . '/../../database/migrations/create_prospect_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make([$this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR])
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_prospect_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_prospect_tables.php')[0];

                    file_put_contents($file, file_get_contents(__DIR__.'/../../database/migrations/create_prospect_tables.php.stub'));
                }

                return $filesystem->glob($path.'*_create_prospect_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_prospect_tables.php")
            ->first();
    }
}
