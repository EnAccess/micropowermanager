<?php

namespace Inensus\AngazaSHS\Providers;

use Illuminate\Support\ServiceProvider;
use Inensus\AngazaSHS\Console\Commands\InstallPackage;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Inensus\AngazaSHS\Modules\Api\AngazaSHSApi;


class AngazaSHSServiceProvider extends ServiceProvider
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
        $this->mergeConfigFrom(__DIR__ . '/../../config/angaza-shs.php', 'angaza-shs');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('AngazaSHSApi', AngazaSHSApi::class);
    }

    public function publishConfigFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/angaza-shs.php' => config_path('angaza-shs.php'),
        ]);
    }

    public function publishVueFiles()
    {
        $this->publishes([
            __DIR__ . '/../resources/assets' => resource_path('assets/js/plugins/angaza-shs'),
        ], 'vue-components');
    }

    public function publishMigrations($filesystem)
    {
        $this->publishes([
            __DIR__ . '/../../database/migrations/create_angaza_tables.php.stub'
            => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }


    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');
        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path . '*_create_angaza_tables.php'))) {
                    $file = $filesystem->glob($path . '*_create_angaza_tables.php')[0];
                    file_put_contents($file,
                        file_get_contents(__DIR__ . '/../../database/migrations/create_angaza_tables.php.stub'));
                }
                return $filesystem->glob($path . '*_create_angaza_tables.php');
            })->push($this->app->databasePath() . "/migrations/{$timestamp}_create_angaza_tables.php")
            ->first();
    }
}