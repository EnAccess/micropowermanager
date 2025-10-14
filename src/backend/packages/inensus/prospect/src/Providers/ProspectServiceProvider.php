<?php

namespace Inensus\Prospect\Providers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\Prospect\Console\Commands\InstallPackage;

class ProspectServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class]);
        } else {
            $this->commands([InstallPackage::class]);
        }
    }

    public function register(): void {
        $this->mergeConfigFrom(__DIR__.'/../../config/prospect.php', 'prospect');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
    }

    public function publishConfigFiles(): void {
        $this->publishes([
            __DIR__.'/../../config/prospect.php' => config_path('prospect.php'),
        ]);
    }

    public function publishVueFiles(): void {
        $this->publishes([
            __DIR__.'/../resources/assets' => resource_path(
                'assets/js/plugins/prospect'
            ),
        ], 'vue-components');
    }

    public function publishMigrations(Filesystem $filesystem): void {
        $this->publishes([
            __DIR__.'/../../database/migrations/create_prospect_tables.php.stub' => $this->getMigrationFileName($filesystem, 'create_prospect_tables.php'),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem, string $migrationName): string {
        $timestamp = date('Y_m_d_His');

        return Collection::make([$this->app->databasePath().DIRECTORY_SEPARATOR.'migrations'.DIRECTORY_SEPARATOR])
            ->flatMap(function ($path) use ($filesystem, $migrationName) {
                if (count($filesystem->glob($path.'*_'.$migrationName))) {
                    $file = $filesystem->glob($path.'*_'.$migrationName)[0];

                    file_put_contents($file, file_get_contents(__DIR__.'/../../database/migrations/'.$migrationName.'.stub'));
                }

                return $filesystem->glob($path.'*_'.$migrationName);
            })->push($this->app->databasePath()."/migrations/tenant/{$timestamp}_{$migrationName}")
            ->first();
    }
}
