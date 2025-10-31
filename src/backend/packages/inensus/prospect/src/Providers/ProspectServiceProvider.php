<?php

namespace Inensus\Prospect\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\Prospect\Console\Commands\InstallPackage;
use Inensus\Prospect\Console\Commands\ProspectDataSynchronizer;

class ProspectServiceProvider extends ServiceProvider {
    public function boot(Filesystem $filesystem): void {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishVueFiles();
            $this->publishMigrations($filesystem);
            $this->commands([InstallPackage::class, ProspectDataSynchronizer::class]);
        } else {
            $this->commands([InstallPackage::class, ProspectDataSynchronizer::class]);
        }

        $this->app->booted(function ($app) {
            $app->make(Schedule::class)->command('prospect:dataSync')->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));
        });
    }

    public function register(): void {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
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
