<?php

namespace Inensus\DalyBms\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Inensus\DalyBms\Console\Commands\CheckPayments;
use Inensus\DalyBms\Console\Commands\InstallPackage;
use Inensus\DalyBms\Console\Commands\SyncBikes;
use Inensus\DalyBms\Modules\Api\DalyBmsApi;
use Illuminate\Filesystem\Filesystem;

class DalyBmsServiceProvider extends ServiceProvider
{
    public function boot(Filesystem $filesystem)
    {
        $this->app->register(RouteServiceProvider::class);
        if ($this->app->runningInConsole()) {
            $this->publishConfigFiles();
            $this->publishMigrations($filesystem);
            $this->commands([
                InstallPackage::class,
                SyncBikes::class,
                CheckPayments::class
            ]);
        }

        $this->app->booted(function ($app) {
            $app->make(Schedule::class)->command('daly-bms:sync-bikes')->withoutOverlapping(50)
                ->appendOutputTo(storage_path('logs/cron.log'));

        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/daly-bms.php', 'daly-bms.php');
        $this->app->register(EventServiceProvider::class);
        $this->app->register(ObserverServiceProvider::class);
        $this->app->bind('DalyBmsApi', DalyBmsApi::class);
    }

    public function publishConfigFiles()
    {
        $this->publishes([
            __DIR__ . '/../../config/daly-bms.php' => config_path('daly-bms.php'),
        ]);
    }

    public function publishMigrations($filesystem)
    {
        $this->publishes([
            __DIR__ . '/../../database/migrations/create_daly_bms_tables.php.stub'
            => $this->getMigrationFileName($filesystem),
        ], 'migrations');
    }

    protected function getMigrationFileName(Filesystem $filesystem): string
    {
        $timestamp = date('Y_m_d_His');
        return Collection::make($this->app->databasePath() . DIRECTORY_SEPARATOR . 'migrations' . DIRECTORY_SEPARATOR)
            ->flatMap(function ($path) use ($filesystem) {
                if (count($filesystem->glob($path . '*_create_daly_bms_tables.php'))) {
                    $file = $filesystem->glob($path . '*_create_daly_bms_tables.php')[0];

                    file_put_contents($file,
                        file_get_contents(__DIR__ . '/../../database/migrations/create_daly_bms_tables.php.stub'));
                }
                return $filesystem->glob($path . '*_create_daly_bms_tables.php');
            })->push($this->app->databasePath() . "/migrations/{$timestamp}_create_daly_bms_tables.php")
            ->first();
    }
}