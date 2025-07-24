<?php

namespace Inensus\AfricasTalking\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\AfricasTalking\AfricasTalkingGateway;
use Inensus\AfricasTalking\Console\Commands\InstallPackage;

class AfricasTalkingServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem) {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
    }

    public function register() {
        $this->mergeConfigFrom(__DIR__.'/../../config/africas-talking.php', 'africas-talking');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('AfricasTalkingGateway', AfricasTalkingGateway::class);
    }

    public function publishConfigFiles() {
        $this->publishes([
            __DIR__.'/../../config/africas-talking.php' => config_path('africas-talking.php'),
        ]);
    }

    public function publishMigrations($filesystem) {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_africas_talking_tables.php.stub' => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make($this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path.'*_create_africas_talking_tables.php'))) {
                    $file = $filesystem->glob($path.'*_create_africas_talking_tables.php')[0];

                    file_put_contents($file, file_get_contents(__DIR__.'/../../database/migrations/create_viber_tables.php.stub'));
                }

                return $filesystem->glob($path.'*_create_africas_talking_tables.php');
            })->push($this->app->databasePath()."/migrations/{$timestamp}_create_africas_talking_tables.php")
            ->first();
    }
}
